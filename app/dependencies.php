<?php

declare(strict_types=1);

use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use App\Application\UseCase\ServiceOrder\ApproveQuotationUseCase;
use App\Application\UseCase\ServiceOrder\CreateServiceOrderUseCase;
use App\Application\UseCase\ServiceOrder\RejectQuotationUseCase;
use App\Application\UseCase\ServiceOrder\TransitionStatusUseCase;
use App\Application\UseCase\Stock\ValidateStockUseCase;
use App\Application\Saga\ServiceOrderSagaOrchestrator;
use App\Domain\Service\NotificationService;
use App\Domain\Service\PartReservationService;
use App\Domain\Service\StockNotificationService;
use App\Infrastructure\Observability\MetricsRegistryInterface;
use App\Infrastructure\Observability\PrometheusMetricsRegistry;
use App\Infrastructure\Messaging\RabbitMqCommandPublisher;
use PhpAmqpLib\Connection\AMQPStreamConnection;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        LoggerInterface::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);

            $loggerSettings = $settings->get('logger');
            $logger = new Logger($loggerSettings['name']);

            $processor = new UidProcessor();
            $logger->pushProcessor($processor);

            $handler = new StreamHandler($loggerSettings['path'], $loggerSettings['level']);
            $logger->pushHandler($handler);

            return $logger;
        },
        EntityManager::class => function () {
            $config = ORMSetup::createAttributeMetadataConfiguration(
                paths: [__DIR__ . '/../src/Domain/Entity'],
                isDevMode: true
            );
            $config->setNamingStrategy(new \Doctrine\ORM\Mapping\UnderscoreNamingStrategy());

            $connection = DriverManager::getConnection([
                'driver' => getenv('DB_DRIVER') ?: 'pdo_mysql',
                'host' => getenv('DB_HOST') ?: '127.0.0.1',
                'port' => getenv('DB_PORT') ?: '3306',
                'dbname' => getenv('DB_NAME') ?: 'os_service',
                'user' => getenv('DB_USER') ?: 'os_user',
                'password' => getenv('DB_PASSWORD') ?: 'os_pass',
            ], $config);

            return new EntityManager($connection, $config);
        },
        EntityManagerInterface::class => \DI\get(EntityManager::class),
        NotificationService::class => \DI\autowire(),
        PartReservationService::class => \DI\autowire(),
        StockNotificationService::class => \DI\autowire(),
        AMQPStreamConnection::class => function () {
            $host = getenv('RABBITMQ_HOST') ?: 'rabbitmq';
            $port = (int)(getenv('RABBITMQ_PORT') ?: 5672);
            $user = getenv('RABBITMQ_USER') ?: 'guest';
            $password = getenv('RABBITMQ_PASSWORD') ?: 'guest';
            $vhost = getenv('RABBITMQ_VHOST') ?: '/';

            return new AMQPStreamConnection($host, $port, $user, $password, $vhost);
        },
        RabbitMqCommandPublisher::class => \DI\autowire(),
        ServiceOrderSagaOrchestrator::class => \DI\autowire(),
        CreateServiceOrderUseCase::class => \DI\autowire(),
        ApproveQuotationUseCase::class => \DI\autowire(),
        RejectQuotationUseCase::class => \DI\autowire(),
        TransitionStatusUseCase::class => \DI\autowire(),
        ValidateStockUseCase::class => \DI\autowire(),
        MetricsRegistryInterface::class => function () {
            return new PrometheusMetricsRegistry();
        },
    ]);
};
