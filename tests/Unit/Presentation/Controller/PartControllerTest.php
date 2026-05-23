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
    private function createPart(string $name, float $price, int $stock, ?int $min): Part
    {
        $part = new Part($name, new Money($price));
        $part->addStock($stock);
        $part->setMinimumStock($min);
        return $part;
    }

    private function createController($repo): PartController
    {
        $stockService = $this->createMock(StockNotificationService::class);
        return new PartController($repo, $stockService);
    }

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

    // other tests omitted for brevity in this suite
}
