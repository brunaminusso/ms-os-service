<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Domain\Entity\Client;
use App\Domain\Entity\ServiceOrder;
use App\Domain\Entity\Vehicle;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\VehiclePlate;
use App\Domain\Service\NotificationService;
use App\Domain\Repository\NotificationRepositoryInterface;
use PHPUnit\Framework\TestCase;

class NotificationServiceTest extends TestCase
{
    public function testNotifyPartBackInStockSavesNotification(): void
    {
        $repo = $this->createMock(NotificationRepositoryInterface::class);
        $repo->expects($this->exactly(2))->method('save');

        $service = new NotificationService($repo);

        $client = new Client('Cliente A');
        $service->notifyPartBackInStock($client, 'Filtro', 3, null);
    }

    public function testNotifyServiceOrderCreatedSavesNotificationTwice(): void
    {
        $repo = $this->createMock(NotificationRepositoryInterface::class);
        $repo->expects($this->exactly(2))->method('save');

        $service = new NotificationService($repo);

        $client = new Client('Cliente B');
        $vehicle = new Vehicle(new VehiclePlate('XYZ-9999'), 'Make', 'Model', 2021, $client);
        $order = new ServiceOrder('ORD-1', $client, $vehicle);

        $service->notifyServiceOrderCreated($order);
    }

    public function testNotifyStatusChangedUsesMessages(): void
    {
        $repo = $this->createMock(NotificationRepositoryInterface::class);
        $repo->expects($this->exactly(2))->method('save');

        $service = new NotificationService($repo);

        $client = new Client('Cliente C');
        $vehicle = new Vehicle(new VehiclePlate('ZZZ-0000'), 'Make', 'Model', 2022, $client);
        $order = new ServiceOrder('ORD-2', $client, $vehicle);

        $service->notifyStatusChanged($order, 'received');
    }

    public function testAllNotificationsCallSaveTwice(): void
    {
        $repo = $this->createMock(NotificationRepositoryInterface::class);
        // 9 notification methods * 2 saves each = 18
        $repo->expects($this->exactly(18))->method('save');

        $service = new NotificationService($repo);

        $client = new Client('Cliente Full');
        $vehicle = new Vehicle(new VehiclePlate('ABC-1234'), 'Make', 'Model', 2021, $client);
        $order = new ServiceOrder('FULL-1', $client, $vehicle);

        $service->notifyServiceOrderCreated($order);
        $service->notifyStatusChanged($order, 'received');
        $service->notifyQuotationCreated($order);
        $service->notifyQuotationReminder($order);
        $service->notifyQuotationApproved($order);
        $service->notifyQuotationRejected($order);
        $service->notifyOrderCancelled($order, 'motivo');
        $service->notifyPartUnavailable($order, 'Peça A');
        $service->notifyPartBackInStock($client, 'Peça A', 5, $order);
    }
}


