<?php

declare(strict_types=1);

namespace Tests\Domain\ValueObject;

use App\Domain\ValueObject\CNPJ;
use PHPUnit\Framework\TestCase;

class CNPJTest extends TestCase
{
    public function testValidCnpjFormats(): void
    {
        $cnpj = new CNPJ('11.222.333/0001-81');

        $this->assertSame('11222333000181', $cnpj->getValue());
        $this->assertSame('11.222.333/0001-81', $cnpj->getFormatted());
    }

    public function testRejectsInvalidCnpj(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CNPJ('11.111.111/1111-11');
    }

    public function testRejectsInvalidLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CNPJ('123');
    }

    public function testRejectsInvalidCheckDigits(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CNPJ('11.222.333/0001-82');
    }

    public function testRejectsEmptyValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CNPJ('');
    }
}
