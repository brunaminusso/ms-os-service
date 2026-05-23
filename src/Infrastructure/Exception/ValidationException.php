<?php

declare(strict_types=1);

namespace App\Infrastructure\Exception;

use Exception;

class ValidationException extends Exception
{
    /**
     * @var array<string,mixed>
     */
    private array $errors;

    /**
     * @param string|array<string,mixed> $errors
     */
    public function __construct(string|array $errors)
    {
        if (is_string($errors)) {
            $this->errors = ['message' => $errors];
            parent::__construct($errors, 422);
        } else {
            $this->errors = $errors;
            parent::__construct('Erro de validação', 422);
        }
    }

    /**
     * @return array<string,mixed>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
