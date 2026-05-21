<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Domain\Entity\Part;
use App\Domain\Repository\PartRepositoryInterface;
use App\Domain\Service\StockNotificationService;
use App\Domain\ValueObject\Money;
use Psr\Http\Message\ResponseInterface as Response;
use App\Infrastructure\Exception\NotFoundException;
use App\Infrastructure\Exception\ValidationException;
use Psr\Http\Message\ServerRequestInterface as Request;

class PartController extends BaseController
{
    public function __construct(
        private PartRepositoryInterface $partRepository,
        private StockNotificationService $stockNotificationService
    ) {
    }

    public function list(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $page = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 20);

        $parts = $this->partRepository->findAll($page, $limit);

        $data = array_map(fn($part) => $this->partToArray($part), $parts);

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
        $part = $this->partRepository->findById($id);

        if (!$part) {
            $response->getBody()->write(json_encode(['error' => self::MSG_NOT_FOUND_PART]));
            return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON)->withStatus(404);
        }

        $response->getBody()->write(json_encode($this->partToArray($part)));
        return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
    }

    public function getByCode(Request $request, Response $response, array $args): Response
    {
        $code = $args['code'];
        $part = $this->partRepository->findByCode($code);

        if (!$part) {
            $response->getBody()->write(json_encode(['error' => 'Peça não encontrada com o código fornecido']));
            return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON)->withStatus(404);
        }

        $response->getBody()->write(json_encode($this->partToArray($part)));
        return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
    }

    public function create(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        try {
            if (empty($data['name']) || empty($data['price'])) {
                throw new \InvalidArgumentException('Nome e preço são obrigatórios');
            }

            $price = new Money((float)$data['price']);
            $part = new Part($data['name'], $price);

            if (isset($data['code'])) {
                $part->setCode($data['code']);
            }

            if (isset($data['description'])) {
                $part->setDescription($data['description']);
            }

            if (isset($data['stock_quantity'])) {
                $part->addStock((int)$data['stock_quantity']);
            }

            if (isset($data['minimum_stock'])) {
                $part->setMinimumStock((int)$data['minimum_stock']);
            }

            if (isset($data['active'])) {
                $part->setActive((bool)$data['active']);
            }

            $this->partRepository->save($part);

            $response->getBody()->write(json_encode($this->partToArray($part)));
            return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON)->withStatus(201);
        } catch (\InvalidArgumentException $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON)->withStatus(400);
        }
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $part = $this->partRepository->findById($id);

        if (!$part) {
            $response->getBody()->write(json_encode(['error' => self::MSG_NOT_FOUND_PART]));
            return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON)->withStatus(404);
        }

        $data = $request->getParsedBody();

        try {
            if (isset($data['name'])) {
                $part->setName($data['name']);
            }

            if (isset($data['code'])) {
                $part->setCode($data['code']);
            }

            if (isset($data['description'])) {
                $part->setDescription($data['description']);
            }

            if (isset($data['price'])) {
                $price = new Money((float)$data['price']);
                $part->setPrice($price);
            }

            if (isset($data['minimum_stock'])) {
                $part->setMinimumStock((int)$data['minimum_stock']);
            }

            if (isset($data['active'])) {
                $part->setActive((bool)$data['active']);
            }

            $this->partRepository->save($part);

            $response->getBody()->write(json_encode($this->partToArray($part)));
            return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
        } catch (\InvalidArgumentException $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON)->withStatus(400);
        }
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $part = $this->partRepository->findById($id);

        if (!$part) {
            $response->getBody()->write(json_encode(['error' => self::MSG_NOT_FOUND_PART]));
            return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON)->withStatus(404);
        }

        // Soft delete - marca como inativo ao invés de deletar
        $part->setActive(false);
        $this->partRepository->save($part);

        $response->getBody()->write(json_encode(['message' => 'Peça desativada com sucesso']));
        return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
    }

    public function updateStock(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $part = $this->partRepository->findById($id);

        if (!$part) {
            throw new NotFoundException('Peça', $id);
        }

        $data = $request->getParsedBody();

        if (!isset($data['quantity'])) {
            throw new ValidationException('Quantidade é obrigatória');
        }

        $quantity = (int)$data['quantity'];
        $operation = $data['operation'] ?? 'add';

        if (!in_array($operation, ['add', 'remove'])) {
            throw new ValidationException('Operação inválida. Use "add" ou "remove"');
        }

        $wasLowStock = $part->isLowStock();

        if ($operation === 'add') {
            $part->addStock($quantity);

            if ($quantity > 0) {
                $notifiedOrders = $this->stockNotificationService->notifyPartRestocked($part);

                $this->partRepository->save($part);

                $responseData = $this->partToArray($part);

                if (!empty($notifiedOrders)) {
                    $responseData['notifications'] = [
                        'sent' => count($notifiedOrders),
                        'orders_notified' => $notifiedOrders,
                        'message' => sprintf(
                            '%d ordem(ns) de serviço aguardando esta peça foi(foram) notificada(s)',
                            count($notifiedOrders)
                        )
                    ];
                }

                if ($wasLowStock && !$part->isLowStock()) {
                    $responseData['stock_status'] = 'Estoque normalizado (saiu do nível crítico)';
                }

                $response->getBody()->write(json_encode($responseData));
                return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
            }
        } elseif ($operation === 'remove') {
            $part->removeStock($quantity);
        }

        $this->partRepository->save($part);

        $responseData = $this->partToArray($part);

        if (!$wasLowStock && $part->isLowStock()) {
            $responseData['warning'] = 'Atenção: Estoque abaixo do nível mínimo!';
        }

        $response->getBody()->write(json_encode($responseData));
        return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
    }

    public function getLowStock(Request $request, Response $response): Response
    {
        $parts = $this->partRepository->findLowStock();

        $data = array_map(fn($part) => $this->partToArray($part), $parts);

        $response->getBody()->write(json_encode(['data' => $data]));
        return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
    }

    private function partToArray(Part $part): array
    {
        return [
            'id' => $part->getId(),
            'name' => $part->getName(),
            'code' => $part->getCode(),
            'description' => $part->getDescription(),
            'price' => $part->getPrice()->getFormatted(),
            'stock_quantity' => $part->getStockQuantity(),
            'minimum_stock' => $part->getMinimumStock(),
            'is_low_stock' => $part->isLowStock(),
            'active' => $part->isActive(),
            'created_at' => $part->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $part->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];
    }
}
