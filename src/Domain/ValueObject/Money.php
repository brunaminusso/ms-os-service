<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

final class Money
{
    private int $amountInCents;
    private string $currency;

    public function __construct(float $amount, string $currency = 'BRL')
    {
        if ($amount < 0) {
            throw new InvalidArgumentException("Valor monetário não pode ser negativo");
        }

        $this->amountInCents = (int)round($amount * 100);
        $this->currency = $currency;
    }

    public static function fromCents(int $cents, string $currency = 'BRL'): self
    {
        $money = new self(0, $currency);
        $money->amountInCents = $cents;
        return $money;
    }

    public function getAmount(): float
    {
        return $this->amountInCents / 100;
    }

    public function getAmountInCents(): int
    {
        return $this->amountInCents;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function add(Money $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException("Não é possível somar valores em moedas diferentes");
        }

        return self::fromCents($this->amountInCents + $other->amountInCents, $this->currency);
    }

    public function subtract(Money $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException("Não é possível subtrair valores em moedas diferentes");
        }

        $result = $this->amountInCents - $other->amountInCents;
        if ($result < 0) {
            throw new InvalidArgumentException("Resultado da subtração não pode ser negativo");
        }

        return self::fromCents($result, $this->currency);
    }

    public function multiply(float $factor): self
    {
        return self::fromCents((int)round($this->amountInCents * $factor), $this->currency);
    }

    public function getFormatted(): string
    {
        return number_format($this->getAmount(), 2, ',', '.');
    }

    public function __toString(): string
    {
        return $this->getFormatted();
    }
}
