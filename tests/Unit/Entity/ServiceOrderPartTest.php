<?php

declare(strict_types=1);

namespace Tests\Unit\Entity;

use App\Domain\Entity\Client;
use App\Domain\Entity\Part;
use App\Domain\Entity\ServiceOrder;
use App\Domain\Entity\Vehicle;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\VehiclePlate;
use PHPUnit\Framework\TestCase;

class ServiceOrderPartTest extends TestCase
{
    public function testConstructAndSubtotal(): void
    {
        $client = new Client('Cl');
        $vehicle = new Vehicle(new VehiclePlate('ABC-1234'), 'Make', 'Model', 2020, $client);
        $order = new ServiceOrder('SOP-1', $client, $vehicle);

        $part = new Part('Filtro', Money::fromCents(1000));
        $part->addStock(5);

        $orderPart = new \App\Domain\Entity\ServiceOrderPart($order, $part, 2);

        $this->assertEquals(2, $orderPart->getQuantity());
        $this->assertEquals(1000, $orderPart->getUnitPrice()->getAmountInCents());
        $this->assertEquals(2000, $orderPart->getSubtotal()->getAmountInCents());
    }

    public function testConstructInvalidQuantityThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $client = new Client('Cl');
        $vehicle = new Vehicle(new VehiclePlate('ABC-1234'), 'Make', 'Model', 2020, $client);
        $order = new ServiceOrder('SOP-2', $client, $vehicle);

        $part = new Part('Filtro', Money::fromCents(1000));
        $part->addStock(5);

        new \App\Domain\Entity\ServiceOrderPart($order, $part, 0);
    }

    public function testConstructInsufficientStockThrows(): void
    {
        $this->expectException(\DomainException::class);

        $client = new Client('Cl');
        $vehicle = new Vehicle(new VehiclePlate('ABC-1234'), 'Make', 'Model', 2020, $client);
        $order = new ServiceOrder('SOP-3', $client, $vehicle);

        $part = new Part('Filtro', Money::fromCents(1000));
        $part->addStock(1);

        new \App\Domain\Entity\ServiceOrderPart($order, $part, 2);
    }
}
