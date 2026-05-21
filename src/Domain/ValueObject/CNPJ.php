<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

final class CNPJ
{
    private string $value;

    public function __construct(string $cnpj)
    {
        $cleaned = preg_replace('/\D/', '', $cnpj);

        if ($cleaned === null || $cleaned === '') {
            throw new InvalidArgumentException('CNPJ não pode ser vazio.');
        }

        if (!$this->isValid($cleaned, $errorMessage)) {
            throw new InvalidArgumentException($errorMessage);
        }

        $this->value = $cleaned;
    }

    private function isValid(string $cnpj, ?string &$errorMessage = null): bool
    {
        if (strlen($cnpj) !== 14) {
            $errorMessage = 'CNPJ deve conter exatamente 14 dígitos. '
                . 'Formato esperado: XX.XXX.XXX/XXXX-XX ou apenas números.';
            return false;
        }

        if (preg_match('/(\d)\1{13}/', $cnpj)) {
            $errorMessage = "CNPJ inválido. Todos os dígitos não podem ser iguais (ex: 11.111.111/1111-11).";
            return false;
        }

        // Validar ambos os dígitos verificadores
        if (!$this->validateCheckDigits($cnpj)) {
            $errorMessage = "CNPJ inválido. Os dígitos verificadores não conferem. Verifique se digitou corretamente.";
            return false;
        }

        return true;
    }

    private function validateCheckDigits(string $cnpj): bool
    {
        // Primeiro dígito verificador
        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int)$cnpj[$i] * $weights1[$i];
        }
        $digit1 = $sum % 11;
        $digit1 = ($digit1 < 2) ? 0 : 11 - $digit1;

        if ((int)$cnpj[12] !== $digit1) {
            return false;
        }

        // Segundo dígito verificador
        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += (int)$cnpj[$i] * $weights2[$i];
        }
        $digit2 = $sum % 11;
        $digit2 = ($digit2 < 2) ? 0 : 11 - $digit2;

        return (int)$cnpj[13] === $digit2;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getFormatted(): string
    {
        return sprintf(
            '%s.%s.%s/%s-%s',
            substr($this->value, 0, 2),
            substr($this->value, 2, 3),
            substr($this->value, 5, 3),
            substr($this->value, 8, 4),
            substr($this->value, 12, 2)
        );
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
