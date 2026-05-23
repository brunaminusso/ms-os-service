<?php

declare(strict_types=1);

namespace Tests\Unit\Entity;

use App\Domain\Entity\Part;
use App\Domain\ValueObject\Money;
use PHPUnit\Framework\TestCase;

class PartTest extends TestCase
{
    public function testAddStockAndRemoveStock(): void
    {
        $part = new Part('Pastilha', Money::fromCents(2000));
        $this->assertEquals(0, $part->getStockQuantity());

        $part->addStock(5);
        $this->assertEquals(5, $part->getStockQuantity());

        $part->removeStock(2);
        $this->assertEquals(3, $part->getStockQuantity());
    }

    public function testAddStockNegativeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $part = new Part('Cabo', Money::fromCents(1000));
        $part->addStock(-1);
    }

    public function testIsLowStockBehavior(): void
    {
        $part = new Part('Sensor', Money::fromCents(500));
        $part->addStock(2);
        $this->assertFalse($part->isLowStock());

        $part->setMinimumStock(3);
        $this->assertTrue($part->isLowStock());
    }
}

