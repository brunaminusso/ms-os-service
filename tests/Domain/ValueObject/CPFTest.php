<?php

declare(strict_types=1);

namespace Tests\Domain\ValueObject;

use App\Domain\ValueObject\CPF;
use PHPUnit\Framework\TestCase;

class CPFTest extends TestCase
{
    public function testValidCpfFormats(): void
    {
        $cpf = new CPF('529.982.247-25');

        $this->assertSame('52998224725', $cpf->getValue());
        $this->assertSame('529.982.247-25', $cpf->getFormatted());
    }

    public function testRejectsInvalidCpf(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CPF('111.111.111-11');
    }

    public function testRejectsInvalidLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CPF('123');
    }

    public function testRejectsInvalidCheckDigits(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CPF('529.982.247-24');
    }

    public function testRejectsEmptyValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CPF('');
    }
}
