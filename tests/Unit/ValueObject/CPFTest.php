<?php

declare(strict_types=1);

namespace Tests\Unit\ValueObject;

use App\Domain\ValueObject\CPF;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CPFTest extends TestCase
{
    public function testValidCpf(): void
    {
        $cpf = new CPF('12345678909'); // valid sample
        $this->assertSame('12345678909', $cpf->getValue());
        $this->assertMatchesRegularExpression('/\d{3}\.\d{3}\.\d{3}-\d{2}/', $cpf->getFormatted());
        $this->assertSame('12345678909', (string)$cpf);
    }

    public function testInvalidCpfThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new CPF('11111111111');
    }
}
