<?php

declare(strict_types=1);

namespace Tests\Unit\Entity;

use App\Domain\Entity\Part;
use App\Domain\ValueObject\Money;
use PHPUnit\Framework\TestCase;

final class PartTest extends TestCase
{
    public function testAddAndRemoveStock(): void
    {
        $part = new Part('Filtro', new Money(10.0));
        $this->assertEquals(0, $part->getStockQuantity());

        $part->addStock(5);
        $this->assertEquals(5, $part->getStockQuantity());

        $part->removeStock(3);
        $this->assertEquals(2, $part->getStockQuantity());
    }

    public function testAddStockNegativeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $part = new Part('Filtro', new Money(10.0));
        $part->addStock(-1);
    }

    public function testRemoveStockInsufficientThrows(): void
    {
        $this->expectException(\DomainException::class);
        $part = new Part('Filtro', new Money(10.0));
        $part->removeStock(1);
    }
}
