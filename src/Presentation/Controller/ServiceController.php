<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Domain\Entity\Service;
use App\Domain\Repository\ServiceRepositoryInterface;
use App\Domain\ValueObject\Money;
use App\Infrastructure\Exception\NotFoundException;
use App\Infrastructure\Exception\ValidationException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ServiceController extends BaseController
{
    public function __construct(
        private ServiceRepositoryInterface $serviceRepository
    ) {
    }

    public function list(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $page = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 20);

        $services = $this->serviceRepository->findAll($page, $limit);

        $data = array_map(fn($service) => $this->serviceToArray($service), $services);

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
        $service = $this->serviceRepository->findById($id);

        if (!$service) {
            throw new NotFoundException('Serviço', $id);
        }

        $response->getBody()->write(json_encode($this->serviceToArray($service)));
        return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
    }

    public function create(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        if (empty($data['name']) || empty($data['price'])) {
            throw new ValidationException('Nome e preço são obrigatórios');
        }

        $price = new Money((float)$data['price']);
        $service = new Service($data['name'], $price);

        if (isset($data['description'])) {
            $service->setDescription($data['description']);
        }

        if (isset($data['estimated_duration_minutes'])) {
            $service->setEstimatedDurationMinutes((int)$data['estimated_duration_minutes']);
        }

        if (isset($data['active'])) {
            $service->setActive((bool)$data['active']);
        }

        $this->serviceRepository->save($service);

        $response->getBody()->write(json_encode($this->serviceToArray($service)));
        return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON)->withStatus(201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $service = $this->serviceRepository->findById($id);

        if (!$service) {
            throw new NotFoundException('Serviço', $id);
        }

        $data = $request->getParsedBody();

        if (isset($data['name'])) {
            $service->setName($data['name']);
        }

        if (isset($data['description'])) {
            $service->setDescription($data['description']);
        }

        if (isset($data['price'])) {
            $price = new Money((float)$data['price']);
            $service->setPrice($price);
        }

        if (isset($data['estimated_duration_minutes'])) {
            $service->setEstimatedDurationMinutes((int)$data['estimated_duration_minutes']);
        }

        if (isset($data['active'])) {
            $service->setActive((bool)$data['active']);
        }

        $this->serviceRepository->save($service);

        $response->getBody()->write(json_encode($this->serviceToArray($service)));
        return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $service = $this->serviceRepository->findById($id);

        if (!$service) {
            throw new NotFoundException('Serviço', $id);
        }

        $service->setActive(false);
        $this->serviceRepository->save($service);

        $response->getBody()->write(json_encode(['message' => 'Serviço desativado com sucesso']));
        return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
    }

    private function serviceToArray(Service $service): array
    {
        return [
            'id' => $service->getId(),
            'name' => $service->getName(),
            'description' => $service->getDescription(),
            'price' => $service->getPrice()->getFormatted(),
            'estimated_duration_minutes' => $service->getEstimatedDurationMinutes(),
            'active' => $service->isActive(),
            'created_at' => $service->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $service->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];
    }
}
