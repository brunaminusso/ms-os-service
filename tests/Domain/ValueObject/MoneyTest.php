<?php

declare(strict_types=1);

namespace Tests\Domain\ValueObject;

use App\Domain\ValueObject\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function testAddsMoneyOfSameCurrency(): void
    {
        $a = new Money(10.0, 'BRL');
        $b = new Money(5.5, 'BRL');

        $sum = $a->add($b);

        $this->assertSame(15.5, $sum->getAmount());
    }

    public function testRejectsDifferentCurrency(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $a = new Money(10.0, 'BRL');
        $b = new Money(10.0, 'USD');

        $a->add($b);
    }

    public function testSubtractsMoney(): void
    {
        $a = new Money(20.0, 'BRL');
        $b = new Money(5.5, 'BRL');

        $result = $a->subtract($b);

        $this->assertSame(14.5, $result->getAmount());
    }

    public function testRejectsNegativeSubtraction(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $a = new Money(10.0, 'BRL');
        $b = new Money(20.0, 'BRL');

        $a->subtract($b);
    }

    public function testRejectsNegativeAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Money(-1.0, 'BRL');
    }

    public function testMultiplyAndFromCents(): void
    {
        $money = Money::fromCents(1234, 'BRL');

        $this->assertSame(12.34, $money->getAmount());
        $this->assertSame(1234, $money->getAmountInCents());
        $this->assertSame('BRL', $money->getCurrency());

        $multiplied = $money->multiply(2.0);
        $this->assertSame(24.68, $multiplied->getAmount());
    }

    public function testFormattedAndToString(): void
    {
        $money = new Money(10.5, 'BRL');

        $this->assertSame('10,50', $money->getFormatted());
        $this->assertSame('10,50', (string)$money);
    }
}
