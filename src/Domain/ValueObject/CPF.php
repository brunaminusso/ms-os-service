<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

final class CPF
{
    private string $value;

    public function __construct(string $cpf)
    {
        $cleaned = preg_replace('/\D/', '', $cpf);

        if ($cleaned === null || $cleaned === '') {
            throw new InvalidArgumentException('CPF não pode ser vazio.');
        }

        if (!$this->isValid($cleaned, $errorMessage)) {
            throw new InvalidArgumentException($errorMessage);
        }

        $this->value = $cleaned;
    }

    private function isValid(string $cpf, ?string &$errorMessage = null): bool
    {
        if (strlen($cpf) !== 11) {
            $errorMessage = "CPF deve conter exatamente 11 dígitos. Formato esperado: XXX.XXX.XXX-XX ou apenas números.";
            return false;
        }

        if (preg_match('/(\d)\1{10}/', $cpf)) {
            $errorMessage = "CPF inválido. Todos os dígitos não podem ser iguais (ex: 111.111.111-11).";
            return false;
        }

        // Validar ambos os dígitos verificadores
        if (!$this->validateCheckDigits($cpf)) {
            $errorMessage = "CPF inválido. Os dígitos verificadores não conferem. Verifique se digitou corretamente.";
            return false;
        }

        return true;
    }

    private function validateCheckDigits(string $cpf): bool
    {
        // Primeiro dígito verificador
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int)$cpf[$i] * (10 - $i);
        }
        $digit1 = ($sum * 10) % 11;
        $digit1 = ($digit1 === 10) ? 0 : $digit1;

        if ((int)$cpf[9] !== $digit1) {
            return false;
        }

        // Segundo dígito verificador
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += (int)$cpf[$i] * (11 - $i);
        }
        $digit2 = ($sum * 10) % 11;
        $digit2 = ($digit2 === 10) ? 0 : $digit2;

        return (int)$cpf[10] === $digit2;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getFormatted(): string
    {
        return sprintf(
            '%s.%s.%s-%s',
            substr($this->value, 0, 3),
            substr($this->value, 3, 3),
            substr($this->value, 6, 3),
            substr($this->value, 9, 2)
        );
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
