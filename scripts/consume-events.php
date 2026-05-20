<?php

declare(strict_types=1);

use App\Application\UseCase\ServiceOrder\TransitionStatusUseCase;
use App\Application\Saga\ServiceOrderSagaOrchestrator;
use App\Domain\Enum\OrderStatus;
use App\Domain\Repository\ServiceOrderRepositoryInterface;
use DI\ContainerBuilder;
use PhpAmqpLib\Connection\AMQPStreamConnection;

require __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->safeLoad();
}

$containerBuilder = new ContainerBuilder();
$settings = require __DIR__ . '/../app/settings.php';
$settings($containerBuilder);
$dependencies = require __DIR__ . '/../app/dependencies.php';
$dependencies($containerBuilder);
$repositories = require __DIR__ . '/../app/repositories.php';
$repositories($containerBuilder);
$container = $containerBuilder->build();

$host = getenv('RABBITMQ_HOST') ?: 'rabbitmq';
$port = (int)(getenv('RABBITMQ_PORT') ?: 5672);
$user = getenv('RABBITMQ_USER') ?: 'guest';
$password = getenv('RABBITMQ_PASSWORD') ?: 'guest';
$vhost = getenv('RABBITMQ_VHOST') ?: '/';

$connection = new AMQPStreamConnection($host, $port, $user, $password, $vhost);
$channel = $connection->channel();

$billingExchange = 'billing.events';
$executionExchange = 'execution.events';

$channel->exchange_declare($billingExchange, 'direct', false, true, false);
$channel->exchange_declare($executionExchange, 'direct', false, true, false);

$queue = 'os-service-events';
$channel->queue_declare($queue, false, true, false, false);
$channel->queue_bind($queue, $billingExchange, 'billing.quotation.created');
$channel->queue_bind($queue, $billingExchange, 'billing.quotation.failed');
$channel->queue_bind($queue, $billingExchange, 'billing.payment.approved');
$channel->queue_bind($queue, $billingExchange, 'billing.payment.failed');
$channel->queue_bind($queue, $billingExchange, 'billing.payment.refund.failed');
$channel->queue_bind($queue, $executionExchange, 'execution.status.updated');
$channel->queue_bind($queue, $executionExchange, 'execution.failed');

$serviceOrderRepository = $container->get(ServiceOrderRepositoryInterface::class);
$transitionStatusUseCase = $container->get(TransitionStatusUseCase::class);
$orchestrator = $container->get(ServiceOrderSagaOrchestrator::class);

$callback = function ($msg) use ($serviceOrderRepository, $transitionStatusUseCase) {
    $payload = json_decode((string)$msg->body, true);

    if (!is_array($payload)) {
        $msg->ack();
        return;
    }

    $serviceOrderId = $payload['service_order_id'] ?? null;
    if (!$serviceOrderId) {
        $msg->ack();
        return;
    }

    $order = $serviceOrderRepository->findById((int)$serviceOrderId);
    if (!$order) {
        $msg->ack();
        return;
    }

    $routingKey = $msg->getRoutingKey();

    if ($routingKey === 'billing.quotation.created') {
        $transitionStatusUseCase->execute($order, OrderStatus::AWAITING_APPROVAL, 'Orcamento gerado pelo Billing Service');
        $msg->ack();
        return;
    }

    if ($routingKey === 'billing.quotation.failed') {
        $reason = (string)($payload['reason'] ?? 'Falha ao gerar orcamento');
        $orchestrator->compensateQuotationFailure($order, $reason);
        $msg->ack();
        return;
    }

    if ($routingKey === 'billing.payment.approved') {
        $orchestrator->startExecution($order, $payload);
        $msg->ack();
        return;
    }

    if ($routingKey === 'execution.failed') {
        $reason = (string)($payload['reason'] ?? 'Falha na execucao');
        $orchestrator->compensateExecutionFailure($order, $reason, $payload);
        $msg->ack();
        return;
    }

    if ($routingKey === 'billing.payment.failed') {
        $reason = (string)($payload['reason'] ?? 'Falha no pagamento');
        $orchestrator->compensatePaymentFailure($order, $reason);
        $msg->ack();
        return;
    }

    if ($routingKey === 'billing.payment.refund.failed') {
        $reason = (string)($payload['reason'] ?? 'Falha no reembolso');
        $orchestrator->handleRefundFailure($order, $reason);
        $msg->ack();
        return;
    }

    if ($routingKey === 'execution.status.updated') {
        $stage = $payload['stage'] ?? null;

        $map = [
            'in_diagnostic' => OrderStatus::IN_DIAGNOSTIC,
            'awaiting_approval' => OrderStatus::AWAITING_APPROVAL,
            'in_execution' => OrderStatus::IN_EXECUTION,
            'finished' => OrderStatus::FINISHED,
            'delivered' => OrderStatus::DELIVERED,
        ];

        if ($stage && isset($map[$stage])) {
            $transitionStatusUseCase->execute($order, $map[$stage], 'Atualizado pelo Execution Service');
        }

        $msg->ack();
        return;
    }

    $msg->ack();
};

$channel->basic_consume($queue, '', false, false, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}
