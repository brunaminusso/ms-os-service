<?php

declare(strict_types=1);

namespace Tests\Unit\Presentation\Controller;

use App\Domain\Entity\Client;
use App\Domain\Entity\Part;
use App\Domain\Entity\ServiceOrder;
use App\Domain\Entity\Vehicle;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\VehiclePlate;
use App\Presentation\Controller\ServiceOrderController;
use App\Application\UseCase\ServiceOrder\CreateServiceOrderUseCase;
use App\Application\UseCase\ServiceOrder\ApproveQuotationUseCase;
use App\Application\UseCase\ServiceOrder\RejectQuotationUseCase;
use App\Application\UseCase\ServiceOrder\TransitionStatusUseCase;
use App\Application\UseCase\Stock\ValidateStockUseCase;
use App\Domain\Repository\ServiceOrderRepositoryInterface;
use App\Domain\Repository\ClientRepositoryInterface;
use App\Domain\Repository\VehicleRepositoryInterface;
use App\Domain\Repository\ServiceRepositoryInterface;
use App\Domain\Repository\PartRepositoryInterface;
use App\Infrastructure\Observability\MetricsRegistryInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Response;

final class ServiceOrderControllerTest extends TestCase
{
    public function testGetReturns404WhenNotFound(): void
    {
        $serviceOrderRepo = $this->createMock(ServiceOrderRepositoryInterface::class);
        $serviceOrderRepo->method('findById')->willReturn(null);

        $controller = new ServiceOrderController(
            $serviceOrderRepo,
            $this->createMock(ClientRepositoryInterface::class),
            $this->createMock(VehicleRepositoryInterface::class),
            $this->createMock(ServiceRepositoryInterface::class),
            $this->createMock(PartRepositoryInterface::class),
            $this->createMock(CreateServiceOrderUseCase::class),
            $this->createMock(ApproveQuotationUseCase::class),
            $this->createMock(RejectQuotationUseCase::class),
            $this->createMock(TransitionStatusUseCase::class),
            $this->createMock(ValidateStockUseCase::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(MetricsRegistryInterface::class)
        );

        $request = $this->createMock(\Psr\Http\Message\ServerRequestInterface::class);
        $response = new Response();

        $result = $controller->get($request, $response, ['id' => 1]);

        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testCreateSuccessfulReturns201WithOrderData(): void
    {
        $serviceOrderRepo = $this->createMock(ServiceOrderRepositoryInterface::class);
        $serviceOrderRepo->method('nextOrderNumber')->willReturn('ORD-1');

        $client = new Client('Cliente Test');
        $vehicle = new Vehicle(
            new VehiclePlate('ABC1234'),
            'Brand',
            'Model',
            2020,
            $client
        );

        $clientRepo = $this->createMock(ClientRepositoryInterface::class);
        $clientRepo->method('findById')->willReturn($client);

        $vehicleRepo = $this->createMock(VehicleRepositoryInterface::class);
        $vehicleRepo->method('findById')->willReturn($vehicle);

        $createUseCase = $this->createMock(CreateServiceOrderUseCase::class);
        $createUseCase->expects($this->once())->method('execute');

        $metrics = $this->createMock(MetricsRegistryInterface::class);
        $metrics->expects($this->once())->method('incOrdersCreated');

        $controller = new ServiceOrderController(
            $serviceOrderRepo,
            $clientRepo,
            $vehicleRepo,
            $this->createMock(ServiceRepositoryInterface::class),
            $this->createMock(PartRepositoryInterface::class),
            $createUseCase,
            $this->createMock(ApproveQuotationUseCase::class),
            $this->createMock(RejectQuotationUseCase::class),
            $this->createMock(TransitionStatusUseCase::class),
            $this->createMock(ValidateStockUseCase::class),
            $this->createMock(LoggerInterface::class),
            $metrics
        );

        $request = (new \Slim\Psr7\Factory\ServerRequestFactory())->createServerRequest('POST', '/orders')
            ->withParsedBody(['client_id' => 1, 'vehicle_id' => 1, 'description' => 'Teste']);

        $response = new Response();

        $result = $controller->create($request, $response);

        $this->assertEquals(201, $result->getStatusCode());
        $body = json_decode((string)$result->getBody(), true);
        $this->assertEquals('ORD-1', $body['order_number']);
        $this->assertEquals('Teste', $body['description']);
    }

    public function testAddPartReturnsWarningWhenLowStock(): void
    {
        $client = new Client('Cliente P');
        $vehicle = new Vehicle(
            new VehiclePlate('XYZ9876'),
            'Brand',
            'Model',
            2021,
            $client
        );
        $order = new ServiceOrder('ORD-2', $client, $vehicle);

        $part = new Part('Filtro', new Money(10.0));
        $part->addStock(10);

        $serviceOrderRepo = $this->createMock(ServiceOrderRepositoryInterface::class);
        $serviceOrderRepo->method('findById')->willReturn($order);
        $serviceOrderRepo->expects($this->once())->method('save');

        $partRepo = $this->createMock(PartRepositoryInterface::class);
        $partRepo->method('findById')->willReturn($part);

        $validate = $this->createMock(ValidateStockUseCase::class);
        $validate->method('execute')->willReturn([
            'warning' => true,
            'available' => 1,
            'requested' => 5
        ]);

        $controller = new ServiceOrderController(
            $serviceOrderRepo,
            $this->createMock(ClientRepositoryInterface::class),
            $this->createMock(VehicleRepositoryInterface::class),
            $this->createMock(ServiceRepositoryInterface::class),
            $partRepo,
            $this->createMock(CreateServiceOrderUseCase::class),
            $this->createMock(ApproveQuotationUseCase::class),
            $this->createMock(RejectQuotationUseCase::class),
            $this->createMock(TransitionStatusUseCase::class),
            $validate,
            $this->createMock(LoggerInterface::class),
            $this->createMock(MetricsRegistryInterface::class)
        );

        $request = (new \Slim\Psr7\Factory\ServerRequestFactory())->createServerRequest('POST', '/orders/1/parts')
            ->withParsedBody(['part_id' => 1, 'quantity' => 5]);

        $response = new Response();

        $result = $controller->addPart($request, $response, ['id' => 1]);

        $this->assertEquals(200, $result->getStatusCode());
        $body = json_decode((string)$result->getBody(), true);
        $this->assertArrayHasKey('warning', $body);
        $this->assertEquals(1, $body['available_stock']);
        $this->assertEquals(5, $body['requested_quantity']);
    }

    public function testListReturnsOrdersWhenStatusParamPresent(): void
    {
        $client = new \App\Domain\Entity\Client('Cliente L');
        $vehicle = new \App\Domain\Entity\Vehicle(
            new \App\Domain\ValueObject\VehiclePlate('ZZZ9999'),
            'Brand',
            'Model',
            2020,
            $client
        );
        $order = new \App\Domain\Entity\ServiceOrder('ORD-200', $client, $vehicle);

        $serviceOrderRepo = $this->createMock(ServiceOrderRepositoryInterface::class);
        $serviceOrderRepo->method('findByStatus')->willReturn([$order]);

        $controller = new ServiceOrderController(
            $serviceOrderRepo,
            $this->createMock(ClientRepositoryInterface::class),
            $this->createMock(VehicleRepositoryInterface::class),
            $this->createMock(ServiceRepositoryInterface::class),
            $this->createMock(PartRepositoryInterface::class),
            $this->createMock(CreateServiceOrderUseCase::class),
            $this->createMock(ApproveQuotationUseCase::class),
            $this->createMock(RejectQuotationUseCase::class),
            $this->createMock(TransitionStatusUseCase::class),
            $this->createMock(ValidateStockUseCase::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(MetricsRegistryInterface::class)
        );

        $request = (new \Slim\Psr7\Factory\ServerRequestFactory())->createServerRequest('GET', '/orders')
            ->withQueryParams(['status' => 'received']);
        $response = new \Slim\Psr7\Response();

        $result = $controller->list($request, $response);
        $this->assertEquals(200, $result->getStatusCode());
        $body = json_decode((string)$result->getBody(), true);
        $this->assertCount(1, $body['data']);
        $this->assertEquals('ORD-200', $body['data'][0]['order_number']);
    }

    public function testUpdateStatusCallsUseCaseAndReturns200(): void
    {
        $client = new \App\Domain\Entity\Client('Cliente S');
        $vehicle = new \App\Domain\Entity\Vehicle(
            new \App\Domain\ValueObject\VehiclePlate('SSS1111'),
            'Brand',
            'Model',
            2020,
            $client
        );
        $order = new \App\Domain\Entity\ServiceOrder('ORD-300', $client, $vehicle);

        $serviceOrderRepo = $this->createMock(ServiceOrderRepositoryInterface::class);
        $serviceOrderRepo->method('findById')->willReturn($order);
        $serviceOrderRepo->expects($this->once())->method('save');

        $notification = $this->createMock(\App\Domain\Service\NotificationService::class);
        $reservation = $this->createMock(\App\Domain\Service\PartReservationService::class);

        $transitionUseCase = new \App\Application\UseCase\ServiceOrder\TransitionStatusUseCase(
            $serviceOrderRepo,
            $notification,
            $reservation
        );

        $controller = new ServiceOrderController(
            $serviceOrderRepo,
            $this->createMock(ClientRepositoryInterface::class),
            $this->createMock(VehicleRepositoryInterface::class),
            $this->createMock(ServiceRepositoryInterface::class),
            $this->createMock(PartRepositoryInterface::class),
            $this->createMock(CreateServiceOrderUseCase::class),
            $this->createMock(ApproveQuotationUseCase::class),
            $this->createMock(RejectQuotationUseCase::class),
            $transitionUseCase,
            $this->createMock(ValidateStockUseCase::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(MetricsRegistryInterface::class)
        );

        $request = (new \Slim\Psr7\Factory\ServerRequestFactory())->createServerRequest('POST', '/orders/1/status')
            ->withParsedBody(['status' => 'in_diagnostic']);
        $response = new \Slim\Psr7\Response();

        $result = $controller->updateStatus($request, $response, ['id' => 1]);
        $this->assertEquals(200, $result->getStatusCode());
        $body = json_decode((string)$result->getBody(), true);
        $this->assertEquals('in_diagnostic', $body['status']);
    }

    public function testApproveQuotationCallsUseCaseAndReturns200(): void
    {
        $client = new \App\Domain\Entity\Client('Cliente A');
        $vehicle = new \App\Domain\Entity\Vehicle(
            new \App\Domain\ValueObject\VehiclePlate('AAA1111'),
            'Brand',
            'Model',
            2020,
            $client
        );
        $order = new \App\Domain\Entity\ServiceOrder('ORD-400', $client, $vehicle);

        // move to diagnostic then to awaiting approval
        $order->changeStatus(\App\Domain\Enum\OrderStatus::IN_DIAGNOSTIC);
        $order->sendQuotation();

        $serviceOrderRepo = $this->createMock(ServiceOrderRepositoryInterface::class);
        $serviceOrderRepo->method('findById')->willReturn($order);

        $approveUseCase = $this->createMock(ApproveQuotationUseCase::class);
        $approveUseCase->expects($this->once())->method('execute')->with($order)->willReturnCallback(function ($o) {
            $o->approveQuotation();
            return $o;
        });

        $controller = new ServiceOrderController(
            $serviceOrderRepo,
            $this->createMock(ClientRepositoryInterface::class),
            $this->createMock(VehicleRepositoryInterface::class),
            $this->createMock(ServiceRepositoryInterface::class),
            $this->createMock(PartRepositoryInterface::class),
            $this->createMock(CreateServiceOrderUseCase::class),
            $approveUseCase,
            $this->createMock(RejectQuotationUseCase::class),
            $this->createMock(TransitionStatusUseCase::class),
            $this->createMock(ValidateStockUseCase::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(MetricsRegistryInterface::class)
        );

        $request = $this->createMock(\Psr\Http\Message\ServerRequestInterface::class);
        $response = new \Slim\Psr7\Response();

        $result = $controller->approveQuotation($request, $response, ['id' => 1]);
        $this->assertEquals(200, $result->getStatusCode());
        $body = json_decode((string)$result->getBody(), true);
        $this->assertTrue($body['quotation_approved']);
    }
}
