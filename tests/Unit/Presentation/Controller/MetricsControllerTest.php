<?php

declare(strict_types=1);

namespace Tests\Unit\Presentation\Controller;

use App\Infrastructure\Observability\MetricsRegistryInterface;
use App\Presentation\Controller\MetricsController;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;

final class MetricsControllerTest extends TestCase
{
    public function testIndexReturnsMetricsPayload(): void
    {
        $metrics = $this->createMock(MetricsRegistryInterface::class);
        $metrics->expects($this->once())->method('render')->willReturn('metric_value 1');

        $controller = new MetricsController($metrics);
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/metrics');

        $response = $controller->index($request, new Response());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/plain; version=0.0.4', $response->getHeaderLine('Content-Type'));
        $this->assertSame('metric_value 1', (string)$response->getBody());
    }
}
