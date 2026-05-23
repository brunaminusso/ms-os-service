<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Domain\Entity\Client;
use App\Domain\Entity\ServiceOrder;
use App\Domain\Service\NotificationService;
use App\Domain\Repository\NotificationRepositoryInterface;
use App\Domain\ValueObject\VehiclePlate;
use App\Domain\Entity\Vehicle;
use PHPUnit\Framework\TestCase;

final class NotificationServiceTest extends TestCase
{
    public function testNotifyServiceOrderCreatedSavesNotificationTwice(): void
    {
        $repo = $this->createMock(NotificationRepositoryInterface::class);
        $repo->expects($this->exactly(2))->method('save');

        $service = new NotificationService($repo);

        $client = new Client('Cliente X');
        $plate = new VehiclePlate('ABC1234');
        $vehicle = new Vehicle($plate, 'Brand', 'Model', 2020, $client);
        $order = new ServiceOrder('ORD-10', $client, $vehicle);

        $service->notifyServiceOrderCreated($order);
        // expectations on mock will validate save called twice
        $this->assertTrue(true);
    }

    public function testNotifyPartBackInStockWithoutOrder(): void
    {
        $repo = $this->createMock(NotificationRepositoryInterface::class);
        $repo->expects($this->exactly(2))->method('save');

        $service = new NotificationService($repo);

        $client = new Client('Cliente Y');
        $service->notifyPartBackInStock($client, 'Filtro', 5, null);
    }

    public function testNotifyPartBackInStockWithOrder(): void
    {
        $repo = $this->createMock(NotificationRepositoryInterface::class);
        $repo->expects($this->exactly(2))->method('save');

        $service = new NotificationService($repo);

        $client = new Client('Cliente Z');
        $plate = new VehiclePlate('ABC1234');
        $vehicle = new Vehicle($plate, 'Brand', 'Model', 2020, $client);
        $order = new ServiceOrder('ORD-11', $client, $vehicle);

        $service->notifyPartBackInStock($client, 'Filtro', 2, $order);
    }
}
