<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Domain\Entity\Client;
use App\Domain\Repository\ClientRepositoryInterface;
use App\Domain\ValueObject\CPF;
use App\Domain\ValueObject\CNPJ;
use App\Infrastructure\Exception\NotFoundException;
use App\Infrastructure\Exception\ValidationException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ClientController extends BaseController
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository
    ) {}

    public function list(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $page = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 20);

        $clients = $this->clientRepository->findAll($page, $limit);

        $data = array_map(fn($client) => $this->clientToArray($client), $clients);

        $response->getBody()->write(json_encode([
            'data' => $data,
            'page' => $page,
            'limit' => $limit
        ]));

        return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
    }

    public function get(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $client = $this->clientRepository->findById($id);

        if (!$client) {
            throw new NotFoundException('Cliente', $id);
        }

        $response->getBody()->write(json_encode($this->clientToArray($client)));
        return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
    }

    public function create(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        if (empty($data['name'])) {
            throw new ValidationException('Nome é obrigatório');
        }

        $client = new Client($data['name']);

        if (isset($data['cpf'])) {
            $client->setCpf(new CPF($data['cpf']));
        } elseif (isset($data['cnpj'])) {
            $client->setCnpj(new CNPJ($data['cnpj']));
        }

        if (isset($data['email'])) {
            $client->setEmail($data['email']);
        }

        if (isset($data['phone'])) {
            $client->setPhone($data['phone']);
        }

        if (isset($data['address'])) {
            $client->setAddress($data['address']);
        }

        $this->clientRepository->save($client);

        $response->getBody()->write(json_encode($this->clientToArray($client)));
        return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON)->withStatus(201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $client = $this->clientRepository->findById($id);

        if (!$client) {
            throw new NotFoundException('Cliente', $id);
        }

        $data = $request->getParsedBody();

        if (isset($data['name'])) {
            $client->setName($data['name']);
        }

        if (isset($data['cpf'])) {
            $client->setCpf(new CPF($data['cpf']));
        } elseif (isset($data['cnpj'])) {
            $client->setCnpj(new CNPJ($data['cnpj']));
        }

        if (isset($data['email'])) {
            $client->setEmail($data['email']);
        }

        if (isset($data['phone'])) {
            $client->setPhone($data['phone']);
        }

        if (isset($data['address'])) {
            $client->setAddress($data['address']);
        }

        $this->clientRepository->save($client);

        $response->getBody()->write(json_encode($this->clientToArray($client)));
        return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $client = $this->clientRepository->findById($id);

        if (!$client) {
            throw new NotFoundException('Cliente', $id);
        }

        $this->clientRepository->delete($client);
        $response->getBody()->write(json_encode(['message' => 'Cliente excluído com sucesso']));
        return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
    }

    private function clientToArray(Client $client): array
    {
        try {
            $cpf = $client->getCpf()?->getFormatted();
        } catch (\InvalidArgumentException $e) {
            $cpf = null;
        }

        try {
            $cnpj = $client->getCnpj()?->getFormatted();
        } catch (\InvalidArgumentException $e) {
            $cnpj = null;
        }

        return [
            'id' => $client->getId(),
            'name' => $client->getName(),
            'cpf' => $cpf,
            'cnpj' => $cnpj,
            'email' => $client->getEmail(),
            'phone' => $client->getPhone(),
            'address' => $client->getAddress(),
            'created_at' => $client->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $client->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];
    }
}
