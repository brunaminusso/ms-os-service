<?php

declare(strict_types=1);

namespace Tests\Unit\Handlers;

use App\Application\Handlers\HttpErrorHandler;
use App\Infrastructure\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Throwable;

class HttpErrorHandlerTest extends TestCase
{
    public function testRespondWithNotFoundAndGenericException(): void
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())
            ->method('write')
            ->with($this->stringContains('RESOURCE_NOT_FOUND'));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);
        $response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();

        $factory = $this->createMock(ResponseFactoryInterface::class);
        $factory->method('createResponse')->willReturn($response);

        $handler = new class ($factory, false) extends HttpErrorHandler {
            public function __construct($factory, $displayErrorDetails)
            {
                // Bypass parent constructor and set needed protected properties.
                $this->responseFactory = $factory;
                $this->displayErrorDetails = $displayErrorDetails;
            }

            public function respondTo(Throwable $e): ResponseInterface
            {
                $this->exception = $e;
                return $this->respond();
            }
        };

        $res = $handler->respondTo(new NotFoundException('Ordem', 1));
        $this->assertSame($response, $res);

        // Now test a generic exception with displayErrorDetails enabled.
        $stream2 = $this->createMock(StreamInterface::class);
        $stream2->expects($this->once())
            ->method('write')
            ->with($this->stringContains('server_error'));

        $response2 = $this->createMock(ResponseInterface::class);
        $response2->method('getBody')->willReturn($stream2);
        $response2->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();

        $factory2 = $this->createMock(ResponseFactoryInterface::class);
        $factory2->method('createResponse')->willReturn($response2);

        $handler2 = new class ($factory2, true) extends HttpErrorHandler {
            public function __construct($factory, $displayErrorDetails)
            {
                $this->responseFactory = $factory;
                $this->displayErrorDetails = $displayErrorDetails;
            }

            public function respondTo(Throwable $e): ResponseInterface
            {
                $this->exception = $e;
                return $this->respond();
            }
        };

        $res2 = $handler2->respondTo(new \RuntimeException('boom'));
        $this->assertSame($response2, $res2);
    }
}
