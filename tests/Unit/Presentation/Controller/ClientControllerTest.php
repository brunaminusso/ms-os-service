<?php

declare(strict_types=1);

namespace Tests\Unit\Presentation\Controller;

use App\Domain\Entity\Client;
use App\Domain\Repository\ClientRepositoryInterface;
use App\Domain\ValueObject\CPF;
use App\Domain\ValueObject\CNPJ;
use App\Infrastructure\Exception\NotFoundException;
use App\Infrastructure\Exception\ValidationException;
use App\Presentation\Controller\ClientController;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;

final class ClientControllerTest extends TestCase
{
    public function testListReturnsPaginatedClients(): void
    {
        $client = $this->createClient();

        $clientRepository = $this->createMock(ClientRepositoryInterface::class);
        $clientRepository->expects($this->once())
            ->method('findAll')
            ->with(2, 10)
            ->willReturn([$client]);

        $controller = $this->createController($clientRepository);

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/clients')
            ->withQueryParams(['page' => 2, 'limit' => 10]);

        $response = new Response();
        $result = $controller->list($request, $response);

        $body = json_decode((string)$result->getBody(), true);
        $this->assertSame(2, $body['page']);
        $this->assertSame('Cliente A', $body['data'][0]['name']);
    }
<?php

declare(strict_types=1);

namespace Tests\Unit\Presentation\Controller;

use App\Domain\Entity\Client;
use App\Domain\Repository\ClientRepositoryInterface;
use App\Domain\ValueObject\CPF;
use App\Domain\ValueObject\CNPJ;
use App\Infrastructure\Exception\NotFoundException;
use App\Infrastructure\Exception\ValidationException;
use App\Presentation\Controller\ClientController;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;

final class ClientControllerTest extends TestCase
{
    public function testListReturnsPaginatedClients(): void
    {
        $client = $this->createClient();

        $clientRepository = $this->createMock(ClientRepositoryInterface::class);
        $clientRepository->expects($this->once())
            ->method('findAll')
            ->with(2, 10)
            ->willReturn([$client]);

        $controller = $this->createController($clientRepository);

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/clients')
            ->withQueryParams(['page' => 2, 'limit' => 10]);

        $response = new Response();
        $result = $controller->list($request, $response);

        $body = json_decode((string)$result->getBody(), true);
        $this->assertSame(2, $body['page']);
        $this->assertSame('Cliente A', $body['data'][0]['name']);
    }

    public function testGetReturnsClient(): void
    {
        $client = $this->createClient();

        $clientRepository = $this->createMock(ClientRepositoryInterface::class);
        $clientRepository->method('findById')->willReturn($client);

        $controller = $this->createController($clientRepository);
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/clients/1');
        $response = new Response();

        $result = $controller->get($request, $response, ['id' => 1]);

        $body = json_decode((string)$result->getBody(), true);
        $this->assertSame('Cliente A', $body['name']);
        $this->assertSame('529.982.247-25', $body['cpf']);
    }

    public function testGetThrowsWhenClientIsMissing(): void
    {
        $clientRepository = $this->createMock(ClientRepositoryInterface::class);
        $clientRepository->method('findById')->willReturn(null);

        $controller = $this->createController($clientRepository);

        $this->expectException(NotFoundException::class);
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/clients/1');
        $response = new Response();

        $controller->get($request, $response, ['id' => 1]);
    }

    public function testCreateReturns201WithCpf(): void
    {
        $clientRepository = $this->createMock(ClientRepositoryInterface::class);
        $clientRepository->expects($this->once())->method('save');

        $controller = $this->createController($clientRepository);
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/clients')
            ->withParsedBody([
                'name' => 'Cliente B',
                'cpf' => '529.982.247-25',
                'email' => 'cliente@example.com',
                'phone' => '(11) 99999-9999',
                'address' => 'Rua A, 123',
            ]);

        $response = new Response();
        $result = $controller->create($request, $response);

        $body = json_decode((string)$result->getBody(), true);
        $this->assertSame(201, $result->getStatusCode());
        $this->assertSame('Cliente B', $body['name']);
        $this->assertSame('529.982.247-25', $body['cpf']);
        $this->assertSame('cliente@example.com', $body['email']);
    }

    public function testCreateReturns201WithCnpj(): void
    {
        $clientRepository = $this->createMock(ClientRepositoryInterface::class);
        $clientRepository->expects($this->once())->method('save');

        $controller = $this->createController($clientRepository);
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/clients')
            ->withParsedBody([
                'name' => 'Cliente CNPJ',
                'cnpj' => '11.222.333/0001-81',
            ]);

        $response = new Response();
        $result = $controller->create($request, $response);

        $body = json_decode((string)$result->getBody(), true);
        $this->assertSame(201, $result->getStatusCode());
        $this->assertSame('Cliente CNPJ', $body['name']);
        $this->assertSame('11.222.333/0001-81', $body['cnpj']);
    }

    public function testCreateThrowsWhenNameIsMissing(): void
    {
        $controller = $this->createController($this->createMock(ClientRepositoryInterface::class));

        $this->expectException(ValidationException::class);
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/clients')
            ->withParsedBody([
                'cpf' => '529.982.247-25',
            ]);
        $response = new Response();

        $controller->create($request, $response);
    }

    public function testUpdateReturns200WithCnpj(): void
    {
        $client = $this->createClient();

        $clientRepository = $this->createMock(ClientRepositoryInterface::class);
        $clientRepository->method('findById')->willReturn($client);
        $clientRepository->expects($this->once())->method('save')->with($client);

        $controller = $this->createController($clientRepository);
        $request = (new ServerRequestFactory())->createServerRequest('PUT', '/clients/1')
            ->withParsedBody([
                'name' => 'Cliente Atualizado',
                'cnpj' => '11.222.333/0001-81',
                'email' => 'novo@example.com',
                'phone' => '(11) 88888-8888',
                'address' => 'Rua B, 456',
            ]);

        $response = new Response();
        $result = $controller->update($request, $response, ['id' => 1]);

        $body = json_decode((string)$result->getBody(), true);
        $this->assertSame('Cliente Atualizado', $body['name']);
        $this->assertSame('11.222.333/0001-81', $body['cnpj']);
    }

    public function testUpdateThrowsWhenClientIsMissing(): void
    {
        $clientRepository = $this->createMock(ClientRepositoryInterface::class);
        $clientRepository->method('findById')->willReturn(null);

        $controller = $this->createController($clientRepository);

        $this->expectException(NotFoundException::class);
        $request = (new ServerRequestFactory())->createServerRequest('PUT', '/clients/1');
        $response = new Response();

        $controller->update($request, $response, ['id' => 1]);
    }

    public function testDeleteRemovesClient(): void
    {
        $client = $this->createClient();

        $clientRepository = $this->createMock(ClientRepositoryInterface::class);
        $clientRepository->method('findById')->willReturn($client);
        $clientRepository->expects($this->once())->method('delete')->with($client);

        $controller = $this->createController($clientRepository);
        $request = (new ServerRequestFactory())->createServerRequest('DELETE', '/clients/1');
        $response = new Response();

        $result = $controller->delete($request, $response, ['id' => 1]);

        $this->assertSame(200, $result->getStatusCode());
    }

    public function testDeleteThrowsWhenClientIsMissing(): void
    {
        $clientRepository = $this->createMock(ClientRepositoryInterface::class);
        $clientRepository->method('findById')->willReturn(null);

        $controller = $this->createController($clientRepository);

        $this->expectException(NotFoundException::class);
        $request = (new ServerRequestFactory())->createServerRequest('DELETE', '/clients/1');
        $response = new Response();

        $controller->delete($request, $response, ['id' => 1]);
    }

    private function createController(ClientRepositoryInterface $clientRepository): ClientController
    {
        return new ClientController($clientRepository);
    }

    private function createClient(): Client
    {
        $client = new Client('Cliente A');
        $client->setCpf(new CPF('529.982.247-25'));
        return $client;
    }
}
