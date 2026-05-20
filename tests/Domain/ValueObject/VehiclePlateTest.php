<?php

declare(strict_types=1);

namespace Tests\Domain\ValueObject;

use App\Domain\ValueObject\VehiclePlate;
use PHPUnit\Framework\TestCase;

class VehiclePlateTest extends TestCase
{
    public function testValidPlateFormats(): void
    {
        $plate = new VehiclePlate('ABC1D23');

        $this->assertSame('ABC1D23', $plate->getValue());
        $this->assertSame('ABC-1D23', $plate->getFormatted());
    }

    public function testRejectsInvalidPlate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new VehiclePlate('1234');
    }

    public function testValidOldPlateFormat(): void
    {
        $plate = new VehiclePlate('ABC1234');

        $this->assertSame('ABC1234', $plate->getValue());
        $this->assertSame('ABC-1234', $plate->getFormatted());
    }

    public function testRejectsEmptyPlate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new VehiclePlate('');
    }
}
