<?php

declare(strict_types=1);

namespace Tests\Unit\Handlers;

use App\Application\Handlers\ShutdownHandler;
use App\Application\Handlers\HttpErrorHandler;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpInternalServerErrorException;

class ShutdownHandlerTest extends TestCase
{
    public function testInvokeCallsErrorHandlerAndEmitsResponse(): void
    {
        $request = $this->createMock(Request::class);
        $errorHandler = $this->createMock(HttpErrorHandler::class);

        $response = new \Slim\Psr7\Response();

        $errorHandler->expects($this->once())
            ->method('__invoke')
            ->with(
                $request,
                $this->isInstanceOf(HttpInternalServerErrorException::class),
                true,
                false,
                false
            )
            ->willReturn($response);

        $handler = new ShutdownHandler($request, $errorHandler, true);

        // trigger a user warning so error_get_last() returns a value
        $prev = set_error_handler(function () {
            // suppress PHPUnit treating the warning as a failure
            return true;
        });
        trigger_error('shutdown-test-warning', E_USER_WARNING);
        // restore previous handler so PHPUnit behavior is unchanged
        if ($prev !== null) {
            restore_error_handler();
        }

        $handler->__invoke();

        // clear last error to avoid affecting other tests
        if (function_exists('error_clear_last')) {
            error_clear_last();
        }

        $this->assertTrue(true);
    }
}
