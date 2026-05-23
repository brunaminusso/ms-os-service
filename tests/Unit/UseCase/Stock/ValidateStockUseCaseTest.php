<?php

declare(strict_types=1);

namespace Tests\Unit\UseCase\Stock;

use App\Application\UseCase\Stock\ValidateStockUseCase;
use App\Domain\Entity\Client;
use App\Domain\Entity\Part;
use App\Domain\Entity\ServiceOrder;
use App\Domain\Entity\Vehicle;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\VehiclePlate;
use App\Domain\Service\NotificationService;
use PHPUnit\Framework\TestCase;

class ValidateStockUseCaseTest extends TestCase
{
    public function testExecuteNotifiesWhenInsufficient(): void
    {
        $notification = $this->createMock(NotificationService::class);
        $notification->expects($this->once())
            ->method('notifyPartUnavailable')
            ->with($this->isInstanceOf(ServiceOrder::class), 'Filtro');

        $client = new Client('Cliente');
        $vehicle = new Vehicle(new VehiclePlate('ABC-1234'), 'Brand', 'Model', 2021, $client);
        $order = new ServiceOrder('SO-200', $client, $vehicle);

        $part = new Part('Filtro', Money::fromCents(1000));
        $part->addStock(1);

        $useCase = new ValidateStockUseCase($notification);
        $result = $useCase->execute($order, $part, 2);

        $this->assertFalse($result['sufficient']);
        $this->assertSame(1, $result['available']);
        $this->assertStringContainsString('estoque insuficiente', $result['warning']);
    }

    public function testHasAvailabilityAndGetAvailableQuantity(): void
    {
        $notification = $this->createMock(NotificationService::class);
        $useCase = new ValidateStockUseCase($notification);

        $part = new Part('Filtro', Money::fromCents(1000));
        $part->addStock(5);

        $this->assertTrue($useCase->hasAvailability($part, 3));
        $this->assertSame(5, $useCase->getAvailableQuantity($part));
    }
}
