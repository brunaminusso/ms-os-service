<?php

declare(strict_types=1);

namespace Tests\Unit\Presentation\Controller;

use App\Domain\Entity\Client;
use App\Domain\Entity\Part;
use App\Domain\Entity\ServiceOrder;
use App\Domain\Entity\Vehicle;
use App\Domain\Enum\OrderStatus;
use App\Domain\Repository\PartRepositoryInterface;
use App\Domain\Service\StockNotificationService;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\VehiclePlate;
use App\Infrastructure\Exception\NotFoundException;
use App\Infrastructure\Exception\ValidationException;
use App\Presentation\Controller\PartController;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;

final class PartControllerTest extends TestCase
{
    public function testListReturnsPaginatedParts(): void
    {
        $part = $this->createPart('Filtro', 10.0, 5, 2);

        $partRepository = $this->createMock(PartRepositoryInterface::class);
        $partRepository->expects($this->once())
            ->method('findAll')
            ->with(2, 10)
            ->willReturn([$part]);

        $controller = $this->createController($partRepository);

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/parts')
            ->withQueryParams(['page' => 2, 'limit' => 10]);

        $result = $controller->list($request, new Response());

        $body = json_decode((string)$result->getBody(), true);
        $this->assertSame(2, $body['page']);
        $this->assertSame(10, $body['limit']);
        $this->assertSame('Filtro', $body['data'][0]['name']);
    }

    public function testGetReturns404WhenPartNotFound(): void
    {
        $partRepository = $this->createMock(PartRepositoryInterface::class);
        $partRepository->method('findById')->willReturn(null);

        $controller = $this->createController($partRepository);
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/parts/1');
        $response = new Response();

        $result = $controller->get($request, $response, ['id' => 1]);

        $this->assertSame(404, $result->getStatusCode());
    }

    public function testGetByCodeReturns404WhenPartNotFound(): void
    {
        $partRepository = $this->createMock(PartRepositoryInterface::class);
        $partRepository->method('findByCode')->willReturn(null);

        $controller = $this->createController($partRepository);
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/parts/F-1');
        $response = new Response();

        $result = $controller->getByCode($request, $response, ['code' => 'F-1']);

        $this->assertSame(404, $result->getStatusCode());
    }

    public function testCreateReturns201WithOptionalFields(): void
    {
        $partRepository = $this->createMock(PartRepositoryInterface::class);
        $partRepository->expects($this->once())->method('save');

        $controller = $this->createController($partRepository);

        $request = (new ServerRequestFactory())->createServerRequest('POST', '/parts')
            ->withParsedBody([
                'name' => 'Filtro',
                'price' => 25.5,
                'code' => 'F-01',
                'description' => 'Filtro de óleo',
                'stock_quantity' => 8,
                'minimum_stock' => 3,
                'active' => true,
            ]);

        $result = $controller->create($request, new Response());

        $this->assertSame(201, $result->getStatusCode());
        $body = json_decode((string)$result->getBody(), true);
        $this->assertSame('Filtro', $body['name']);
        $this->assertSame('F-01', $body['code']);
        $this->assertSame(8, $body['stock_quantity']);
        $this->assertSame(3, $body['minimum_stock']);
        $this->assertTrue($body['active']);
    }

    public function testCreateReturns400WhenRequiredFieldsAreMissing(): void
    {
        $controller = $this->createController($this->createMock(PartRepositoryInterface::class));
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/parts')
            ->withParsedBody(['name' => 'Filtro']);

        $result = $controller->create($request, new Response());

        $this->assertSame(400, $result->getStatusCode());
    }

    public function testUpdateReturns200WhenPartExists(): void
    {
        $part = $this->createPart('Filtro', 10.0, 5, 2);

        $partRepository = $this->createMock(PartRepositoryInterface::class);
        $partRepository->expects($this->once())->method('findById')->with(1)->willReturn($part);
        $partRepository->expects($this->once())->method('save');

        $controller = $this->createController($partRepository);

        $request = (new ServerRequestFactory())->createServerRequest('PUT', '/parts/1')
            ->withParsedBody([
                'name' => 'Filtro novo',
                'code' => 'F-02',
                'description' => 'Atualizado',
                'price' => 31.75,
                'minimum_stock' => 4,
                'active' => false,
            ]);

        $response = new Response();
        $result = $controller->update($request, $response, ['id' => 1]);

        $this->assertSame(200, $result->getStatusCode());
        $body = json_decode((string)$result->getBody(), true);
        $this->assertSame('Filtro novo', $body['name']);
        $this->assertSame('F-02', $body['code']);
        $this->assertFalse($body['active']);
    }

    public function testUpdateReturns404WhenPartDoesNotExist(): void
    {
        $partRepository = $this->createMock(PartRepositoryInterface::class);
        $partRepository->method('findById')->willReturn(null);

        $controller = $this->createController($partRepository);
        $request = (new ServerRequestFactory())->createServerRequest('PUT', '/parts/1');
        $response = new Response();

        $result = $controller->update($request, $response, ['id' => 1]);

        $this->assertSame(404, $result->getStatusCode());
    }

    public function testDeleteSoftDeletesPart(): void
    {
        $part = $this->createPart('Filtro', 10.0, 5, 2);

        $partRepository = $this->createMock(PartRepositoryInterface::class);
        $partRepository->expects($this->once())->method('findById')->with(1)->willReturn($part);
        $partRepository->expects($this->once())->method('save')->with($part);

        $controller = $this->createController($partRepository);
        $request = (new ServerRequestFactory())->createServerRequest('DELETE', '/parts/1');
        $response = new Response();

        $result = $controller->delete($request, $response, ['id' => 1]);

        $this->assertSame(200, $result->getStatusCode());
        $this->assertFalse($part->isActive());
    }

    public function testDeleteReturns404WhenPartDoesNotExist(): void
    {
        $partRepository = $this->createMock(PartRepositoryInterface::class);
        $partRepository->method('findById')->willReturn(null);

        $controller = $this->createController($partRepository);
        $request = (new ServerRequestFactory())->createServerRequest('DELETE', '/parts/1');
        $response = new Response();

        $result = $controller->delete($request, $response, ['id' => 1]);

        $this->assertSame(404, $result->getStatusCode());
    }

    public function testUpdateStockAddReturnsNotificationsAndStockStatus(): void
    {
        $part = $this->createPart('Filtro', 10.0, 2, 3);
        $part->setCode('F-10');

        $client = new Client('Cliente A');
        $vehicle = new Vehicle(new VehiclePlate('ABC1234'), 'Brand', 'Model', 2020, $client);
        $order = new ServiceOrder('ORD-1', $client, $vehicle);
        $order->changeStatus(OrderStatus::IN_DIAGNOSTIC);
        $order->addPart($part, 1);

        $partRepository = $this->createMock(PartRepositoryInterface::class);
        $partRepository->expects($this->once())->method('findById')->with(1)->willReturn($part);
        $partRepository->expects($this->once())->method('save')->with($part);

        $stockNotificationService = $this->createMock(StockNotificationService::class);
        $stockNotificationService->expects($this->once())
            ->method('notifyPartRestocked')
            ->with($part)
            ->willReturn([
                [
                    'order_id' => 1,
                    'order_number' => 'ORD-1',
                    'client_name' => 'Cliente A',
                    'part_name' => 'Filtro',
                ],
            ]);

        $controller = $this->createController($partRepository, $stockNotificationService);

        $request = (new ServerRequestFactory())->createServerRequest('POST', '/parts/1/stock')
            ->withParsedBody([
                'quantity' => 5,
                'operation' => 'add',
            ]);

        $result = $controller->updateStock($request, new Response(), ['id' => 1]);

        $body = json_decode((string)$result->getBody(), true);
        $this->assertSame(200, $result->getStatusCode());
        $this->assertSame(7, $body['stock_quantity']);
        $this->assertSame('Estoque normalizado (saiu do nível crítico)', $body['stock_status']);
        $this->assertSame(1, $body['notifications']['sent']);
    }

    public function testUpdateStockRemoveReturnsWarningWhenStockTurnsLow(): void
    {
        $part = $this->createPart('Filtro', 10.0, 4, 3);

        $partRepository = $this->createMock(PartRepositoryInterface::class);
        $partRepository->expects($this->once())->method('findById')->with(1)->willReturn($part);
        $partRepository->expects($this->once())->method('save')->with($part);

        $controller = $this->createController($partRepository);

        $request = (new ServerRequestFactory())->createServerRequest('POST', '/parts/1/stock')
            ->withParsedBody([
                'quantity' => 2,
                'operation' => 'remove',
            ]);

        $result = $controller->updateStock($request, new Response(), ['id' => 1]);

        $body = json_decode((string)$result->getBody(), true);
        $this->assertSame(200, $result->getStatusCode());
        $this->assertSame(2, $body['stock_quantity']);
        $this->assertSame('Atenção: Estoque abaixo do nível mínimo!', $body['warning']);
    }

    public function testUpdateStockThrowsValidationExceptionWhenQuantityMissing(): void
    {
        $part = $this->createPart('Filtro', 10.0, 4, 3);

        $partRepository = $this->createMock(PartRepositoryInterface::class);
        $partRepository->method('findById')->willReturn($part);

        $controller = $this->createController($partRepository);

        $this->expectException(ValidationException::class);
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/parts/1/stock')
            ->withParsedBody([
                'operation' => 'add',
            ]);
        $response = new Response();

        $controller->updateStock(
            $request,
            $response,
            ['id' => 1]
        );
    }

    public function testUpdateStockThrowsValidationExceptionWhenOperationIsInvalid(): void
    {
        $part = $this->createPart('Filtro', 10.0, 4, 3);

        $partRepository = $this->createMock(PartRepositoryInterface::class);
        $partRepository->method('findById')->willReturn($part);

        $controller = $this->createController($partRepository);

        $this->expectException(ValidationException::class);
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/parts/1/stock')
            ->withParsedBody([
                'quantity' => 1,
                'operation' => 'bad',
            ]);
        $response = new Response();

        $controller->updateStock(
            $request,
            $response,
            ['id' => 1]
        );
    }

    public function testUpdateStockThrowsNotFoundExceptionWhenPartMissing(): void
    {
        $controller = $this->createController($this->createMock(PartRepositoryInterface::class));

        $this->expectException(NotFoundException::class);
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/parts/1/stock')
            ->withParsedBody([
                'quantity' => 1,
                'operation' => 'add',
            ]);
        $response = new Response();

        $controller->updateStock(
            $request,
            $response,
            ['id' => 1]
        );
    }

    public function testGetLowStockReturnsParts(): void
    {
        $part = $this->createPart('Filtro', 10.0, 2, 5);

        $partRepository = $this->createMock(PartRepositoryInterface::class);
        $partRepository->method('findLowStock')->willReturn([$part]);

        $controller = $this->createController($partRepository);
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/parts/low-stock');
        $response = new Response();

        $result = $controller->getLowStock($request, $response);

        $body = json_decode((string) $result->getBody(), true);
        $this->assertSame(200, $result->getStatusCode());
        $this->assertSame('Filtro', $body['data'][0]['name']);
        $this->assertTrue($body['data'][0]['is_low_stock']);
    }

    private function createController(
        PartRepositoryInterface $partRepository,
        ?StockNotificationService $stockNotificationService = null
    ): PartController {
        return new PartController(
            $partRepository,
            $stockNotificationService ?? $this->createMock(StockNotificationService::class)
        );
    }

    private function createPart(string $name, float $price, int $stockQuantity = 0, ?int $minimumStock = null): Part
    {
        $part = new Part($name, new Money($price));

        if ($stockQuantity > 0) {
            $part->addStock($stockQuantity);
        }

        $part->setMinimumStock($minimumStock);

        return $part;
    }
}
