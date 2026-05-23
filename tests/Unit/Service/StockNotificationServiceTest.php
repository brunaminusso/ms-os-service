<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Domain\Entity\Client;
use App\Domain\Entity\ServiceOrder;
use App\Domain\Entity\Part;
use App\Domain\Entity\Vehicle;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\VehiclePlate;
use App\Domain\Enum\OrderStatus;
use App\Domain\Service\StockNotificationService;
use App\Domain\Service\NotificationService;
use App\Domain\Repository\ServiceOrderRepositoryInterface;
use PHPUnit\Framework\TestCase;

class StockNotificationServiceTest extends TestCase
{
    public function testNotifyPartRestockedNotifiesOrders(): void
    {
        $serviceOrderRepo = $this->createMock(ServiceOrderRepositoryInterface::class);
        $notificationService = $this->createMock(NotificationService::class);

        $client = new Client('Cliente Teste');
        $vehicle = new Vehicle(new VehiclePlate('ABC-1234'), 'Brand', 'Model', 2020, $client);
        $order = new ServiceOrder('SO-100', $client, $vehicle);

        $part = new Part('Filtro', Money::fromCents(1000));
        $part->addStock(5);

        // add part to order (uses ServiceOrder->addPart which validates stock)
        $order->addPart($part, 1);

        // map repository responses for statuses
        $serviceOrderRepo->method('findByStatus')->willReturnMap([
            [OrderStatus::RECEIVED, []],
            [OrderStatus::IN_DIAGNOSTIC, [$order]],
        ]);

        $notificationService->expects($this->once())
            ->method('notifyPartBackInStock')
            ->with($client, $part->getName(), $part->getStockQuantity(), $order);

        $service = new StockNotificationService($serviceOrderRepo, $notificationService);
        $result = $service->notifyPartRestocked($part);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals($order->getOrderNumber(), $result[0]['order_number']);
    }

    public function testNotifyPartRestockedReturnsEmptyWhenNoOrders(): void
    {
        $serviceOrderRepo = $this->createMock(ServiceOrderRepositoryInterface::class);
        $notificationService = $this->createMock(NotificationService::class);

        $part = new Part('Filtro Vazio', Money::fromCents(500));
        $part->addStock(2);

        $serviceOrderRepo->method('findByStatus')->willReturnMap([
            [OrderStatus::RECEIVED, []],
            [OrderStatus::IN_DIAGNOSTIC, []],
        ]);

        $notificationService->expects($this->never())->method('notifyPartBackInStock');

        $service = new StockNotificationService($serviceOrderRepo, $notificationService);
        $result = $service->notifyPartRestocked($part);

        $this->assertSame([], $result);
    }
}
