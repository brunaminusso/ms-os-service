<?php

declare(strict_types=1);

namespace Tests\Unit\Entity;

use App\Domain\Entity\Client;
use App\Domain\Entity\Service;
use App\Domain\Entity\ServiceOrder;
use App\Domain\Entity\Vehicle;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\VehiclePlate;
use PHPUnit\Framework\TestCase;

class ServiceOrderServiceTest extends TestCase
{
    public function testConstructAndSubtotal(): void
    {
        $client = new Client('C');
        $vehicle = new Vehicle(new VehiclePlate('ABC-1234'), 'Make', 'Model', 2020, $client);
        $order = new ServiceOrder('SOS-1', $client, $vehicle);

        $service = new Service('Troca óleo', new Money(50.0));

        $orderService = new \App\Domain\Entity\ServiceOrderService($order, $service, 2);

        $this->assertEquals(2, $orderService->getQuantity());
        $this->assertEquals(5000, $orderService->getUnitPrice()->getAmountInCents());
        $this->assertEquals(10000, $orderService->getSubtotal()->getAmountInCents());
    }

    public function testSetQuantityInvalidThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $client = new Client('C');
        $vehicle = new Vehicle(new VehiclePlate('ABC-1234'), 'Make', 'Model', 2020, $client);
        $order = new ServiceOrder('SOS-2', $client, $vehicle);

        $service = new Service('Troca óleo', new Money(50.0));
        $orderService = new \App\Domain\Entity\ServiceOrderService($order, $service, 1);

        $orderService->setQuantity(0);
    }
}
