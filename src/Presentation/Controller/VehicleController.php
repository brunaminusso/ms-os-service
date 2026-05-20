<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Domain\Entity\Vehicle;
use App\Domain\Repository\VehicleRepositoryInterface;
use App\Domain\Repository\ClientRepositoryInterface;
use App\Domain\ValueObject\VehiclePlate;
use App\Infrastructure\Exception\NotFoundException;
use App\Infrastructure\Exception\ValidationException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class VehicleController extends BaseController
{
    public function __construct(
        private VehicleRepositoryInterface $vehicleRepository,
        private ClientRepositoryInterface $clientRepository
    ) {
    }

    public function list(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $page = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 20);

        $vehicles = $this->vehicleRepository->findAll($page, $limit);

        $data = array_map(fn($vehicle) => $this->vehicleToArray($vehicle), $vehicles);

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
        $vehicle = $this->vehicleRepository->findById($id);

        if (!$vehicle) {
            throw new NotFoundException('Veículo', $id);
        }

        $response->getBody()->write(json_encode($this->vehicleToArray($vehicle)));
        return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
    }

    public function listByClient(Request $request, Response $response, array $args): Response
    {
        $clientId = (int)$args['clientId'];
        $client = $this->clientRepository->findById($clientId);

        if (!$client) {
            throw new NotFoundException('Cliente', $clientId);
        }

        $vehicles = $this->vehicleRepository->findByClientId($clientId);
        $data = array_map(fn($vehicle) => $this->vehicleToArray($vehicle), $vehicles);

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
    }

    public function create(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        if (empty($data['plate']) || empty($data['brand']) || empty($data['model']) || empty($data['year']) || empty($data['client_id'])) {
            throw new ValidationException('Placa, marca, modelo, ano e cliente são obrigatórios');
        }

        $client = $this->clientRepository->findById((int)$data['client_id']);
        if (!$client) {
            throw new NotFoundException('Cliente', $data['client_id']);
        }

        $plate = new VehiclePlate($data['plate']);
        $vehicle = new Vehicle(
            $plate,
            $data['brand'],
            $data['model'],
            (int)$data['year'],
            $client
        );

        if (isset($data['color'])) {
            $vehicle->setColor($data['color']);
        }

        $this->vehicleRepository->save($vehicle);

        $response->getBody()->write(json_encode($this->vehicleToArray($vehicle)));
        return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON)->withStatus(201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $vehicle = $this->vehicleRepository->findById($id);

        if (!$vehicle) {
            throw new NotFoundException('Veículo', $id);
        }

        $data = $request->getParsedBody();

        if (isset($data['plate'])) {
            $vehicle->setPlate(new VehiclePlate($data['plate']));
        }

        if (isset($data['brand'])) {
            $vehicle->setBrand($data['brand']);
        }

        if (isset($data['model'])) {
            $vehicle->setModel($data['model']);
        }

        if (isset($data['year'])) {
            $vehicle->setYear((int)$data['year']);
        }

        if (isset($data['color'])) {
            $vehicle->setColor($data['color']);
        }

        $this->vehicleRepository->save($vehicle);

        $response->getBody()->write(json_encode($this->vehicleToArray($vehicle)));
        return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $vehicle = $this->vehicleRepository->findById($id);

        if (!$vehicle) {
            throw new NotFoundException('Veículo', $id);
        }

        $this->vehicleRepository->delete($vehicle);
        $response->getBody()->write(json_encode(['message' => 'Veículo excluído com sucesso']));
        return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
    }

    private function vehicleToArray(Vehicle $vehicle): array
    {
        return [
            'id' => $vehicle->getId(),
            'plate' => $vehicle->getPlate()->getFormatted(),
            'brand' => $vehicle->getBrand(),
            'model' => $vehicle->getModel(),
            'year' => $vehicle->getYear(),
            'color' => $vehicle->getColor(),
            'client' => [
                'id' => $vehicle->getClient()->getId(),
                'name' => $vehicle->getClient()->getName(),
            ],
            'created_at' => $vehicle->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $vehicle->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];
    }
}
