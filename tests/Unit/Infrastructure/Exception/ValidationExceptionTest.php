<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Exception;

use App\Infrastructure\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

class ValidationExceptionTest extends TestCase
{
    public function testStringErrorsCreatesMessageArray(): void
    {
        $ex = new ValidationException('Error happened');
        $this->assertIsArray($ex->getErrors());
        $this->assertSame(['message' => 'Error happened'], $ex->getErrors());
    }

    public function testArrayErrorsPreserved(): void
    {
        $arr = ['field' => 'missing'];
        $ex = new ValidationException($arr);
        $this->assertSame($arr, $ex->getErrors());
    }
}
