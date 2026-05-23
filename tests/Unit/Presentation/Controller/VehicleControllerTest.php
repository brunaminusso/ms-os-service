<?php

declare(strict_types=1);

namespace Tests\Unit\Presentation\Controller;

use App\Domain\Entity\Client;
use App\Domain\Entity\Vehicle;
use App\Domain\Repository\ClientRepositoryInterface;
use App\Domain\Repository\VehicleRepositoryInterface;
use App\Domain\ValueObject\VehiclePlate;
use App\Infrastructure\Exception\NotFoundException;
use App\Infrastructure\Exception\ValidationException;
use App\Presentation\Controller\VehicleController;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;

final class VehicleControllerTest extends TestCase
{
    public function testListReturnsPaginatedVehicles(): void
    {
        $vehicle = $this->createVehicle();

        $vehicleRepository = $this->createMock(VehicleRepositoryInterface::class);
        $vehicleRepository->expects($this->once())
            ->method('findAll')
            ->with(2, 10)
            ->willReturn([$vehicle]);

        $controller = $this->createController($vehicleRepository);

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/vehicles')
            ->withQueryParams(['page' => 2, 'limit' => 10]);

        $response = new Response();
        $result = $controller->list($request, $response);

        $body = json_decode((string)$result->getBody(), true);
        $this->assertSame(2, $body['page']);
        $this->assertSame('ABC-1234', $body['data'][0]['plate']);
    }

    public function testGetReturnsVehicle(): void
    {
        $vehicle = $this->createVehicle();

        $vehicleRepository = $this->createMock(VehicleRepositoryInterface::class);
        $vehicleRepository->method('findById')->willReturn($vehicle);

        $controller = $this->createController($vehicleRepository);
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/vehicles/1');
        $response = new Response();

        $result = $controller->get($request, $response, ['id' => 1]);

        $body = json_decode((string)$result->getBody(), true);
        $this->assertSame('Brand', $body['brand']);
        $this->assertSame('ABC-1234', $body['plate']);
    }

    public function testGetThrowsWhenVehicleNotFound(): void
    {
        $vehicleRepository = $this->createMock(VehicleRepositoryInterface::class);
        $vehicleRepository->method('findById')->willReturn(null);

        $controller = $this->createController($vehicleRepository);

        $this->expectException(NotFoundException::class);
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/vehicles/1');
        $response = new Response();

        $controller->get($request, $response, ['id' => 1]);
    }

    public function testListByClientReturnsVehicles(): void
    {
        $vehicle = $this->createVehicle();

        $vehicleRepository = $this->createMock(VehicleRepositoryInterface::class);
        $vehicleRepository->method('findByClientId')->willReturn([$vehicle]);

        $clientRepository = $this->createMock(ClientRepositoryInterface::class);
        $clientRepository->method('findById')->willReturn($vehicle->getClient());

        $controller = $this->createController($vehicleRepository, $clientRepository);
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/clients/1/vehicles');
        $response = new Response();

        $result = $controller->listByClient($request, $response, ['clientId' => 1]);

        $body = json_decode((string)$result->getBody(), true);
        $this->assertSame('ABC-1234', $body[0]['plate']);
    }

    public function testListByClientThrowsWhenClientNotFound(): void
    {
        $controller = $this->createController(
            $this->createMock(VehicleRepositoryInterface::class),
            $this->createMock(ClientRepositoryInterface::class)
        );

        $this->expectException(NotFoundException::class);
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/clients/1/vehicles');
        $response = new Response();

        $controller->listByClient($request, $response, ['clientId' => 1]);
    }

    public function testCreateReturns201(): void
    {
        $vehicleRepository = $this->createMock(VehicleRepositoryInterface::class);
        $vehicleRepository->expects($this->once())->method('save');

        $clientRepository = $this->createMock(ClientRepositoryInterface::class);
        $clientRepository->method('findById')->willReturn($this->createClient());

        $controller = $this->createController($vehicleRepository, $clientRepository);

        $request = (new ServerRequestFactory())->createServerRequest('POST', '/vehicles')
            ->withParsedBody([
                'plate' => 'ABC1234',
                'brand' => 'Brand',
                'model' => 'Model',
                'year' => 2020,
                'client_id' => 1,
                'color' => 'Blue',
            ]);

        $result = $controller->create($request, new Response());

        $body = json_decode((string)$result->getBody(), true);
        $this->assertSame(201, $result->getStatusCode());
        $this->assertSame('Blue', $body['color']);
    }

    public function testCreateThrowsWhenRequiredFieldsAreMissing(): void
    {
        $controller = $this->createController(
            $this->createMock(VehicleRepositoryInterface::class),
            $this->createMock(ClientRepositoryInterface::class)
        );

        $this->expectException(ValidationException::class);
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/vehicles')
            ->withParsedBody([
                'plate' => 'ABC1234',
            ]);
        $response = new Response();

        $controller->create($request, $response);
    }

    public function testCreateThrowsWhenClientIsMissing(): void
    {
        $clientRepository = $this->createMock(ClientRepositoryInterface::class);
        $clientRepository->method('findById')->willReturn(null);

        $controller = $this->createController($this->createMock(VehicleRepositoryInterface::class), $clientRepository);

        $this->expectException(NotFoundException::class);
        $controller->create(
            (new ServerRequestFactory())->createServerRequest('POST', '/vehicles')
                ->withParsedBody([
                    'plate' => 'ABC1234',
                    'brand' => 'Brand',
                    'model' => 'Model',
                    'year' => 2020,
                    'client_id' => 1,
                ]),
            new Response()
        );
    }

    public function testUpdateReturns200(): void
    {
        $vehicle = $this->createVehicle();

        $vehicleRepository = $this->createMock(VehicleRepositoryInterface::class);
        $vehicleRepository->method('findById')->willReturn($vehicle);
        $vehicleRepository->expects($this->once())->method('save')->with($vehicle);

        $controller = $this->createController($vehicleRepository);

        $request = (new ServerRequestFactory())->createServerRequest('PUT', '/vehicles/1')
            ->withParsedBody([
                'plate' => 'ZZZ9999',
                'brand' => 'NewBrand',
                'model' => 'NewModel',
                'year' => 2022,
                'color' => 'Red',
            ]);

        $response = new Response();
        $result = $controller->update($request, $response, ['id' => 1]);

        $body = json_decode((string)$result->getBody(), true);
        $this->assertSame('ZZZ-9999', $body['plate']);
        $this->assertSame('Red', $body['color']);
    }

    public function testUpdateThrowsWhenVehicleIsMissing(): void
    {
        $vehicleRepository = $this->createMock(VehicleRepositoryInterface::class);
        $vehicleRepository->method('findById')->willReturn(null);

        $controller = $this->createController($vehicleRepository);

        $this->expectException(NotFoundException::class);
        $request = (new ServerRequestFactory())->createServerRequest('PUT', '/vehicles/1');
        $response = new Response();

        $controller->update($request, $response, ['id' => 1]);
    }

    public function testDeleteRemovesVehicle(): void
    {
        $vehicle = $this->createVehicle();

        $vehicleRepository = $this->createMock(VehicleRepositoryInterface::class);
        $vehicleRepository->method('findById')->willReturn($vehicle);
        $vehicleRepository->expects($this->once())->method('delete')->with($vehicle);

        $controller = $this->createController($vehicleRepository);
        $request = (new ServerRequestFactory())->createServerRequest('DELETE', '/vehicles/1');
        $response = new Response();

        $result = $controller->delete($request, $response, ['id' => 1]);

        $this->assertSame(200, $result->getStatusCode());
    }

    public function testDeleteThrowsWhenVehicleIsMissing(): void
    {
        $vehicleRepository = $this->createMock(VehicleRepositoryInterface::class);
        $vehicleRepository->method('findById')->willReturn(null);

        $controller = $this->createController($vehicleRepository);

        $this->expectException(NotFoundException::class);
        $request = (new ServerRequestFactory())->createServerRequest('DELETE', '/vehicles/1');
        $response = new Response();

        $controller->delete($request, $response, ['id' => 1]);
    }

    private function createController(
        VehicleRepositoryInterface $vehicleRepository,
        ?ClientRepositoryInterface $clientRepository = null
    ): VehicleController {
        return new VehicleController(
            $vehicleRepository,
            $clientRepository ?? $this->createMock(ClientRepositoryInterface::class)
        );
    }

    private function createClient(): Client
    {
        return new Client('Cliente');
    }

    private function createVehicle(): Vehicle
    {
        return new Vehicle(
            new VehiclePlate('ABC1234'),
            'Brand',
            'Model',
            2020,
            $this->createClient()
        );
    }
}
