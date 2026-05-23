<?php

declare(strict_types=1);

namespace Tests\Unit\Presentation\Controller;

use App\Presentation\Controller\HealthController;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;

final class HealthControllerTest extends TestCase
{
    public function testCheckReturnsHealthyPayload(): void
    {
        $controller = new HealthController();
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/health');

        $response = $controller->check($request, new Response());
        $payload = json_decode((string)$response->getBody(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertSame('healthy', $payload['status']);
        $this->assertSame('Tech Challenge API', $payload['service']);
        $this->assertArrayHasKey('timestamp', $payload);
    }

    public function testReadyReturnsReadyPayload(): void
    {
        $controller = new HealthController();
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/ready');

        $response = $controller->ready($request, new Response());
        $payload = json_decode((string)$response->getBody(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertSame('ready', $payload['status']);
        $this->assertSame('Tech Challenge API', $payload['service']);
        $this->assertArrayHasKey('timestamp', $payload);
    }
}
