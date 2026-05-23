<?php

declare(strict_types=1);

namespace Tests\Unit\ValueObject;

use App\Domain\ValueObject\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function testAddSubtractMultiplyAndFormat(): void
    {
        $a = new Money(10.50);
        $b = new Money(5.25);

        $sum = $a->add($b);
        $this->assertEquals(15.75, $sum->getAmount());

        $diff = $a->subtract(new Money(1.50));
        $this->assertEquals(9.00, $diff->getAmount());

        $prod = $b->multiply(2.0);
        $this->assertEquals(10.50, $prod->getAmount());

        $this->assertIsString($a->getFormatted());
    }

    public function testNegativeMoneyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Money(-1);
    }

    public function testDifferentCurrencyOperationsThrow(): void
    {
        $a = new Money(10.0, 'BRL');
        $b = Money::fromCents(500, 'USD');

        $this->expectException(\InvalidArgumentException::class);
        $a->add($b);
    }
}
