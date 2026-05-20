<?php

declare(strict_types=1);

use App\Domain\Repository\ClientRepositoryInterface;
use App\Domain\Repository\NotificationRepositoryInterface;
use App\Domain\Repository\PartRepositoryInterface;
use App\Domain\Repository\PartReservationRepositoryInterface;
use App\Domain\Repository\ServiceOrderRepositoryInterface;
use App\Domain\Repository\ServiceRepositoryInterface;
use App\Domain\Repository\VehicleRepositoryInterface;
use App\Infrastructure\Persistence\Repository\ClientRepository;
use App\Infrastructure\Persistence\Repository\NotificationRepository;
use App\Infrastructure\Persistence\Repository\PartRepository;
use App\Infrastructure\Persistence\Repository\PartReservationRepository;
use App\Infrastructure\Persistence\Repository\ServiceOrderRepository;
use App\Infrastructure\Persistence\Repository\ServiceRepository;
use App\Infrastructure\Persistence\Repository\VehicleRepository;
use DI\ContainerBuilder;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        ClientRepositoryInterface::class => \DI\autowire(ClientRepository::class),
        VehicleRepositoryInterface::class => \DI\autowire(VehicleRepository::class),
        ServiceRepositoryInterface::class => \DI\autowire(ServiceRepository::class),
        PartRepositoryInterface::class => \DI\autowire(PartRepository::class),
        ServiceOrderRepositoryInterface::class => \DI\autowire(ServiceOrderRepository::class),
        NotificationRepositoryInterface::class => \DI\autowire(NotificationRepository::class),
        PartReservationRepositoryInterface::class => \DI\autowire(PartReservationRepository::class),
    ]);
};
