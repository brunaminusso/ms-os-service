<?php

declare(strict_types=1);

namespace Tests\Unit\Presentation\Controller;

use App\Domain\Entity\Service;
use App\Domain\Repository\ServiceRepositoryInterface;
use App\Domain\ValueObject\Money;
use App\Infrastructure\Exception\NotFoundException;
use App\Infrastructure\Exception\ValidationException;
use App\Presentation\Controller\ServiceController;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;

final class ServiceControllerTest extends TestCase
{
    public function testListReturnsPaginatedServices(): void
    {
        $service = $this->createService();

        $serviceRepository = $this->createMock(ServiceRepositoryInterface::class);
        $serviceRepository->expects($this->once())
            ->method('findAll')
            ->with(2, 10)
            ->willReturn([$service]);

        $controller = $this->createController($serviceRepository);

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/services')
            ->withQueryParams(['page' => 2, 'limit' => 10]);

        $response = new Response();
        $result = $controller->list($request, $response);

        $body = json_decode((string) $result->getBody(), true);
        $this->assertSame(2, $body['page']);
        $this->assertSame('Serviço A', $body['data'][0]['name']);
    }

    public function testGetReturnsService(): void
    {
        $service = $this->createService();

        $serviceRepository = $this->createMock(ServiceRepositoryInterface::class);
        $serviceRepository->method('findById')->willReturn($service);

        $controller = $this->createController($serviceRepository);
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/services/1');
        $response = new Response();

        $result = $controller->get($request, $response, ['id' => 1]);

        $body = json_decode((string) $result->getBody(), true);
        $this->assertSame('Serviço A', $body['name']);
        $this->assertSame('120,00', $body['price']);
    }

    public function testGetThrowsWhenServiceIsMissing(): void
    {
        $serviceRepository = $this->createMock(ServiceRepositoryInterface::class);
        $serviceRepository->method('findById')->willReturn(null);

        $controller = $this->createController($serviceRepository);

        $this->expectException(NotFoundException::class);
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/services/1');
        $response = new Response();

        $controller->get($request, $response, ['id' => 1]);
    }

    public function testCreateReturns201(): void
    {
        $serviceRepository = $this->createMock(ServiceRepositoryInterface::class);
        $serviceRepository->expects($this->once())->method('save');

        $controller = $this->createController($serviceRepository);
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/services')
            ->withParsedBody([
                'name' => 'Serviço B',
                'price' => 150.5,
                'description' => 'Descritivo',
                'estimated_duration_minutes' => 90,
                'active' => true,
            ]);

        $response = new Response();
        $result = $controller->create($request, $response);

        $body = json_decode((string) $result->getBody(), true);
        $this->assertSame(201, $result->getStatusCode());
        $this->assertSame('Serviço B', $body['name']);
        $this->assertSame('150,50', $body['price']);
        $this->assertSame(90, $body['estimated_duration_minutes']);
    }

    public function testCreateThrowsWhenRequiredFieldsAreMissing(): void
    {
        $controller = $this->createController($this->createMock(ServiceRepositoryInterface::class));

        $this->expectException(ValidationException::class);
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/services')
            ->withParsedBody(['name' => 'Serviço B']);
        $response = new Response();

        $controller->create($request, $response);
    }

    public function testUpdateReturns200(): void
    {
        $service = $this->createService();

        $serviceRepository = $this->createMock(ServiceRepositoryInterface::class);
        $serviceRepository->method('findById')->willReturn($service);
        $serviceRepository->expects($this->once())->method('save')->with($service);

        $controller = $this->createController($serviceRepository);
        $request = (new ServerRequestFactory())->createServerRequest('PUT', '/services/1')
            ->withParsedBody([
                'name' => 'Serviço C',
                'description' => 'Novo',
                'price' => 200,
                'estimated_duration_minutes' => 120,
                'active' => false,
            ]);

        $response = new Response();
        $result = $controller->update($request, $response, ['id' => 1]);

        $body = json_decode((string) $result->getBody(), true);
        $this->assertSame('Serviço C', $body['name']);
        $this->assertSame('200,00', $body['price']);
        $this->assertFalse($body['active']);
    }

    public function testUpdateThrowsWhenServiceIsMissing(): void
    {
        $serviceRepository = $this->createMock(ServiceRepositoryInterface::class);
        $serviceRepository->method('findById')->willReturn(null);

        $controller = $this->createController($serviceRepository);

        $this->expectException(NotFoundException::class);
        $request = (new ServerRequestFactory())->createServerRequest('PUT', '/services/1');
        $response = new Response();

        $controller->update($request, $response, ['id' => 1]);
    }

    public function testDeleteRemovesService(): void
    {
        $service = $this->createService();

        $serviceRepository = $this->createMock(ServiceRepositoryInterface::class);
        $serviceRepository->method('findById')->willReturn($service);
        $serviceRepository->expects($this->once())->method('save')->with($service);

        $controller = $this->createController($serviceRepository);
        $request = (new ServerRequestFactory())->createServerRequest('DELETE', '/services/1');
        $response = new Response();

        $result = $controller->delete($request, $response, ['id' => 1]);

        $this->assertSame(200, $result->getStatusCode());
        $this->assertFalse($service->isActive());
    }

    public function testDeleteThrowsWhenServiceIsMissing(): void
    {
        $serviceRepository = $this->createMock(ServiceRepositoryInterface::class);
        $serviceRepository->method('findById')->willReturn(null);

        $controller = $this->createController($serviceRepository);

        $this->expectException(NotFoundException::class);
        $request = (new ServerRequestFactory())->createServerRequest('DELETE', '/services/1');
        $response = new Response();

        $controller->delete($request, $response, ['id' => 1]);
    }

    private function createController(ServiceRepositoryInterface $serviceRepository): ServiceController
    {
        return new ServiceController($serviceRepository);
    }

    private function createService(): Service
    {
        $service = new Service('Serviço A', new Money(120.0));
        $service->setDescription('Descrição');
        $service->setEstimatedDurationMinutes(60);
        return $service;
    }
}
