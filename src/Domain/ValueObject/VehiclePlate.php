<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

final class VehiclePlate
{
    private string $value;

    public function __construct(string $plate)
    {
        $cleaned = preg_replace('/[^A-Z0-9]/', '', $plate);

        if ($cleaned === null || $cleaned === '') {
            throw new InvalidArgumentException('Placa de veículo não pode ser vazia.');
        }

        $cleaned = strtoupper($cleaned);

        if (!$this->isValid($cleaned, $errorMessage)) {
            throw new InvalidArgumentException($errorMessage);
        }

        $this->value = $cleaned;
    }

    private function isValid(string $plate, ?string &$errorMessage = null): bool
    {
        // Valida formato Mercosul (ABC1D23) ou antigo (ABC1234)
        $isValid = preg_match('/^[A-Z]{3}\d[A-Z\d]\d{2}$/', $plate) === 1;

        if (!$isValid) {
            $errorMessage = "Placa de veículo inválida. Formato esperado: ABC-1234 (antigo) ou ABC-1D23 (Mercosul).";
        }

        return $isValid;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getFormatted(): string
    {
        return substr($this->value, 0, 3) . '-' . substr($this->value, 3);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
