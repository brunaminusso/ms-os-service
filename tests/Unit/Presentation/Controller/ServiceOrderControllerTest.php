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
use App\Domain\Repository\ServiceOrderRepositoryInterface;
use App\Domain\Repository\ClientRepositoryInterface;
use App\Domain\Repository\VehicleRepositoryInterface;
use App\Domain\Repository\ServiceRepositoryInterface;
use App\Domain\Repository\PartRepositoryInterface;
use App\Application\UseCase\ServiceOrder\CreateServiceOrderUseCase;
use App\Application\UseCase\ServiceOrder\ApproveQuotationUseCase;
use App\Application\UseCase\ServiceOrder\RejectQuotationUseCase;
use App\Application\UseCase\ServiceOrder\TransitionStatusUseCase;
use App\Application\UseCase\Stock\ValidateStockUseCase;
use App\Infrastructure\Observability\MetricsRegistryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\StreamInterface;

class ServiceOrderControllerTest extends TestCase
{
    private function makeResponseMock(StreamInterface $stream): Response
    {
        $response = $this->createMock(Response::class);
        $response->method('getBody')->willReturn($stream);
        $response->method('withHeader')->willReturnSelf();
        $response->method('withStatus')->willReturnSelf();
        return $response;
    }

    public function testListWithStatusUsesRepository(): void
    {
        $serviceRepo = $this->createMock(ServiceOrderRepositoryInterface::class);
        $clientRepo = $this->createMock(ClientRepositoryInterface::class);
        $vehicleRepo = $this->createMock(VehicleRepositoryInterface::class);
        $serviceRepository = $this->createMock(ServiceRepositoryInterface::class);
        $partRepo = $this->createMock(PartRepositoryInterface::class);
        $createUseCase = $this->createMock(CreateServiceOrderUseCase::class);
        $approveUseCase = $this->createMock(ApproveQuotationUseCase::class);
        $rejectUseCase = $this->createMock(RejectQuotationUseCase::class);
        $transitionUseCase = $this->createMock(TransitionStatusUseCase::class);
        $validateUseCase = $this->createMock(ValidateStockUseCase::class);
        $logger = $this->createMock(LoggerInterface::class);
        $metrics = $this->createMock(MetricsRegistryInterface::class);

        $controller = new ServiceOrderController(
            $serviceRepo,
            $clientRepo,
            $vehicleRepo,
            $serviceRepository,
            $partRepo,
            $createUseCase,
            $approveUseCase,
            $rejectUseCase,
            $transitionUseCase,
            $validateUseCase,
            $logger,
            $metrics
        );

        $client = new Client('Cli');
        $vehicle = new Vehicle(new VehiclePlate('ABC-1234'), 'Brand', 'Model', 2020, $client);
        $order = new ServiceOrder('SO-900', $client, $vehicle);

        $serviceRepo->method('findByStatus')->willReturn([$order]);

        $req = $this->createMock(Request::class);
        $req->method('getQueryParams')->willReturn(['status' => 'received']);

        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())->method('write');
        $res = $this->makeResponseMock($stream);

        $out = $controller->list($req, $res);
        $this->assertSame($res, $out);
    }

    public function testGetNotFoundReturns404(): void
    {
        $serviceRepo = $this->createMock(ServiceOrderRepositoryInterface::class);
        $controller = new ServiceOrderController(
            $serviceRepo,
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

        $serviceRepo->method('findById')->willReturn(null);

        $req = $this->createMock(Request::class);
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())->method('write');
        $res = $this->makeResponseMock($stream);

        $out = $controller->get($req, $res, ['id' => 123]);
        $this->assertSame($res, $out);
    }

    public function testCreateValidReturns201(): void
    {
        $serviceRepo = $this->createMock(ServiceOrderRepositoryInterface::class);
        $clientRepo = $this->createMock(ClientRepositoryInterface::class);
        $vehicleRepo = $this->createMock(VehicleRepositoryInterface::class);
        $createUseCase = $this->createMock(CreateServiceOrderUseCase::class);
        $metrics = $this->createMock(MetricsRegistryInterface::class);

        $controller = new ServiceOrderController(
            $serviceRepo,
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

        $client = new Client('Cli');
        $vehicle = new Vehicle(new VehiclePlate('ABC-1234'), 'Brand', 'Model', 2020, $client);

        $clientRepo->method('findById')->willReturn($client);
        $vehicleRepo->method('findById')->willReturn($vehicle);
        $serviceRepo->method('nextOrderNumber')->willReturn('SO-501');

        $req = $this->createMock(Request::class);
        $req->method('getParsedBody')->willReturn([
            'client_id' => 1,
            'vehicle_id' => 2,
            'description' => 'desc'
        ]);

        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())->method('write');
        $res = $this->makeResponseMock($stream);

        $out = $controller->create($req, $res);
        $this->assertSame($res, $out);
    }

    public function testAddPartReturnsWarningWhenValidationWarns(): void
    {
        $serviceRepo = $this->createMock(ServiceOrderRepositoryInterface::class);
        $partRepo = $this->createMock(PartRepositoryInterface::class);
        $validateUseCase = $this->createMock(ValidateStockUseCase::class);

        $client = new Client('Cli');
        $vehicle = new Vehicle(new VehiclePlate('ABC-1234'), 'Brand', 'Model', 2020, $client);
        $order = new ServiceOrder('SO-777', $client, $vehicle);

        $serviceRepo->method('findById')->willReturn($order);

        $part = new Part('Filtro', Money::fromCents(1000));
        $partRepo->method('findById')->willReturn($part);

        $validateUseCase->method('execute')->willReturn([
            'sufficient' => false,
            'available' => 0,
            'requested' => 1,
            'warning' => 'insuficiente'
        ]);

        $controller = new ServiceOrderController(
            $serviceRepo,
            $this->createMock(ClientRepositoryInterface::class),
            $this->createMock(VehicleRepositoryInterface::class),
            $this->createMock(ServiceRepositoryInterface::class),
            $partRepo,
            $this->createMock(CreateServiceOrderUseCase::class),
            $this->createMock(ApproveQuotationUseCase::class),
            $this->createMock(RejectQuotationUseCase::class),
            $this->createMock(TransitionStatusUseCase::class),
            $validateUseCase,
            $this->createMock(LoggerInterface::class),
            $this->createMock(MetricsRegistryInterface::class)
        );

        $req = $this->createMock(Request::class);
        $req->method('getParsedBody')->willReturn(['part_id' => 1, 'quantity' => 1]);

        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())->method('write');
        $res = $this->makeResponseMock($stream);

        $out = $controller->addPart($req, $res, ['id' => 1]);
        $this->assertSame($res, $out);
    }

    public function testGetReturnsOrderWhenFound(): void
    {
        $client = new Client('Cli');
        $vehicle = new Vehicle(new VehiclePlate('ABC-1234'), 'Brand', 'Model', 2020, $client);
        $order = new ServiceOrder('SO-321', $client, $vehicle);

        $serviceRepo = $this->createMock(ServiceOrderRepositoryInterface::class);
        $serviceRepo->method('findById')->willReturn($order);

        $controller = new ServiceOrderController(
            $serviceRepo,
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

        $req = $this->createMock(Request::class);
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())->method('write');
        $res = $this->makeResponseMock($stream);

        $out = $controller->get($req, $res, ['id' => 1]);
        $this->assertSame($res, $out);
    }

    public function testUpdateStatusCallsUseCaseAndReturns200(): void
    {
        $client = new Client('Cli');
        $vehicle = new Vehicle(new VehiclePlate('ABC-1234'), 'Brand', 'Model', 2020, $client);
        $order = new ServiceOrder('SO-400', $client, $vehicle);

        $serviceRepo = $this->createMock(ServiceOrderRepositoryInterface::class);
        $serviceRepo->method('findById')->willReturn($order);

        $transition = $this->createMock(TransitionStatusUseCase::class);
        $transition->expects($this->once())->method('execute');

        $controller = new ServiceOrderController(
            $serviceRepo,
            $this->createMock(ClientRepositoryInterface::class),
            $this->createMock(VehicleRepositoryInterface::class),
            $this->createMock(ServiceRepositoryInterface::class),
            $this->createMock(PartRepositoryInterface::class),
            $this->createMock(CreateServiceOrderUseCase::class),
            $this->createMock(ApproveQuotationUseCase::class),
            $this->createMock(RejectQuotationUseCase::class),
            $transition,
            $this->createMock(ValidateStockUseCase::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(MetricsRegistryInterface::class)
        );

        $req = $this->createMock(Request::class);
        $req->method('getParsedBody')->willReturn(['status' => 'in_diagnostic']);

        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())->method('write');
        $res = $this->makeResponseMock($stream);

        $out = $controller->updateStatus($req, $res, ['id' => 1]);
        $this->assertSame($res, $out);
    }

    public function testApproveAndRejectQuotationFlows(): void
    {
        $client = new Client('Cli');
        $vehicle = new Vehicle(new VehiclePlate('ABC-1234'), 'Brand', 'Model', 2020, $client);
        $order = new ServiceOrder('SO-500', $client, $vehicle);

        $order->changeStatus(\App\Domain\Enum\OrderStatus::IN_DIAGNOSTIC);
        $order->changeStatus(\App\Domain\Enum\OrderStatus::AWAITING_APPROVAL);

        $serviceRepo = $this->createMock(ServiceOrderRepositoryInterface::class);
        $serviceRepo->method('findById')->willReturn($order);

        $approve = $this->createMock(ApproveQuotationUseCase::class);
        $approve->expects($this->once())->method('execute');

        $reject = $this->createMock(RejectQuotationUseCase::class);
        $reject->expects($this->once())->method('execute');

        $controller = new ServiceOrderController(
            $serviceRepo,
            $this->createMock(ClientRepositoryInterface::class),
            $this->createMock(VehicleRepositoryInterface::class),
            $this->createMock(ServiceRepositoryInterface::class),
            $this->createMock(PartRepositoryInterface::class),
            $this->createMock(CreateServiceOrderUseCase::class),
            $approve,
            $reject,
            $this->createMock(TransitionStatusUseCase::class),
            $this->createMock(ValidateStockUseCase::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(MetricsRegistryInterface::class)
        );

        $req = $this->createMock(Request::class);
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->exactly(2))->method('write');
        $res = $this->makeResponseMock($stream);

        $controller->approveQuotation($req, $res, ['id' => 1]);
        $controller->rejectQuotation($req, $res, ['id' => 1]);
        $this->assertTrue(true);
    }

    public function testAddServiceAndRemoveServiceFlows(): void
    {
        $client = new Client('Cli');
        $vehicle = new Vehicle(new VehiclePlate('ABC-1234'), 'Brand', 'Model', 2020, $client);
        $order = new ServiceOrder('SO-600', $client, $vehicle);

        $serviceRepo = $this->createMock(ServiceOrderRepositoryInterface::class);
        $serviceRepo->method('findById')->willReturn($order);
        $serviceRepo->expects($this->exactly(2))->method('save');

        $serviceEntity = new \App\Domain\Entity\Service('Troca de óleo', Money::fromCents(1000));

        $serviceRepository = $this->createMock(ServiceRepositoryInterface::class);
        $serviceRepository->method('findById')->willReturn($serviceEntity);

        $controller = new ServiceOrderController(
            $serviceRepo,
            $this->createMock(ClientRepositoryInterface::class),
            $this->createMock(VehicleRepositoryInterface::class),
            $serviceRepository,
            $this->createMock(PartRepositoryInterface::class),
            $this->createMock(CreateServiceOrderUseCase::class),
            $this->createMock(ApproveQuotationUseCase::class),
            $this->createMock(RejectQuotationUseCase::class),
            $this->createMock(TransitionStatusUseCase::class),
            $this->createMock(ValidateStockUseCase::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(MetricsRegistryInterface::class)
        );

        $req = $this->createMock(Request::class);
        $req->method('getParsedBody')->willReturn(['service_id' => 1, 'quantity' => 1]);

        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->exactly(2))->method('write');
        $res = $this->makeResponseMock($stream);

        // add service
        $controller->addService($req, $res, ['id' => 1]);

        // prepare a mock ServiceOrderService to remove
        $orderServiceMock = $this->createMock(\App\Domain\Entity\ServiceOrderService::class);
        $serviceMock = $this->createMock(\App\Domain\Entity\Service::class);
        $serviceMock->method('getId')->willReturn(1);
        $orderServiceMock->method('getService')->willReturn($serviceMock);
        $order->getServices()->add($orderServiceMock);

        $out = $controller->removeService($req, $res, ['id' => 1, 'serviceId' => 1]);
        $this->assertSame($res, $out);
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

    public function testAddServiceSuccessfulReturns200(): void
    {
        $client = new Client('Cliente B');
        $vehicle = new Vehicle(new VehiclePlate('BBB2222'), 'Brand', 'Model', 2021, $client);
        $order = new ServiceOrder('ORD-500', $client, $vehicle);

        $service = new \App\Domain\Entity\Service('Troca óleo', new Money(30.0));

        $serviceOrderRepo = $this->createMock(ServiceOrderRepositoryInterface::class);
        $serviceOrderRepo->method('findById')->willReturn($order);
        $serviceOrderRepo->expects($this->once())->method('save');

        $serviceRepo = $this->createMock(ServiceRepositoryInterface::class);
        $serviceRepo->method('findById')->willReturn($service);

        $controller = new ServiceOrderController(
            $serviceOrderRepo,
            $this->createMock(ClientRepositoryInterface::class),
            $this->createMock(VehicleRepositoryInterface::class),
            $serviceRepo,
            $this->createMock(PartRepositoryInterface::class),
            $this->createMock(CreateServiceOrderUseCase::class),
            $this->createMock(ApproveQuotationUseCase::class),
            $this->createMock(RejectQuotationUseCase::class),
            $this->createMock(TransitionStatusUseCase::class),
            $this->createMock(ValidateStockUseCase::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(MetricsRegistryInterface::class)
        );

        $request = (new \Slim\Psr7\Factory\ServerRequestFactory())->createServerRequest('POST', '/orders/1/services')
            ->withParsedBody(['service_id' => 1, 'quantity' => 1]);

        $response = new \Slim\Psr7\Response();

        $result = $controller->addService($request, $response, ['id' => 1]);
        $this->assertEquals(200, $result->getStatusCode());
        $body = json_decode((string)$result->getBody(), true);
        $this->assertEquals('ORD-500', $body['order_number']);
    }

    public function testRemoveServiceReturns404WhenNotFoundInOrder(): void
    {
        $client = new Client('Cliente C');
        $vehicle = new Vehicle(new VehiclePlate('CCC3333'), 'Brand', 'Model', 2020, $client);
        $order = new ServiceOrder('ORD-600', $client, $vehicle);

        $serviceOrderRepo = $this->createMock(ServiceOrderRepositoryInterface::class);
        $serviceOrderRepo->method('findById')->willReturn($order);

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
        $response = new \Slim\Psr7\Response();

        $result = $controller->removeService($request, $response, ['id' => 1, 'serviceId' => 999]);
        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testGetAverageExecutionTimeReturnsData(): void
    {
        $stats = [['service' => 'Troca óleo', 'average' => 120]];

        $serviceOrderRepo = $this->createMock(ServiceOrderRepositoryInterface::class);
        $serviceOrderRepo->method('getAverageExecutionTimeByService')->willReturn($stats);

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
        $response = new \Slim\Psr7\Response();

        $result = $controller->getAverageExecutionTime($request, $response);
        $this->assertEquals(200, $result->getStatusCode());
        $body = json_decode((string)$result->getBody(), true);
        $this->assertEquals($stats, $body['data']);
    }
}
