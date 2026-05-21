<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\UseCase\ServiceOrder\CreateServiceOrderUseCase;
use App\Application\UseCase\ServiceOrder\ApproveQuotationUseCase;
use App\Application\UseCase\ServiceOrder\RejectQuotationUseCase;
use App\Application\UseCase\ServiceOrder\TransitionStatusUseCase;
use App\Application\UseCase\Stock\ValidateStockUseCase;
use App\Domain\Entity\ServiceOrder;
use App\Domain\Enum\OrderStatus;
use App\Domain\Repository\ServiceOrderRepositoryInterface;
use App\Domain\Repository\ClientRepositoryInterface;
use App\Domain\Repository\VehicleRepositoryInterface;
use App\Domain\Repository\ServiceRepositoryInterface;
use App\Domain\Repository\PartRepositoryInterface;
use App\Infrastructure\Observability\MetricsRegistryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

class ServiceOrderController extends BaseController
{
    public function __construct(
        private ServiceOrderRepositoryInterface $serviceOrderRepository,
        private ClientRepositoryInterface $clientRepository,
        private VehicleRepositoryInterface $vehicleRepository,
        private ServiceRepositoryInterface $serviceRepository,
        private PartRepositoryInterface $partRepository,
        private CreateServiceOrderUseCase $createServiceOrderUseCase,
        private ApproveQuotationUseCase $approveQuotationUseCase,
        private RejectQuotationUseCase $rejectQuotationUseCase,
        private TransitionStatusUseCase $transitionStatusUseCase,
        private ValidateStockUseCase $validateStockUseCase,
        private LoggerInterface $logger,
        private MetricsRegistryInterface $metrics
    ) {
    }

    public function list(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $page = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 20);

        if (isset($params['status'])) {
            $status = OrderStatus::from($params['status']);
            $orders = $this->serviceOrderRepository->findByStatus($status);
        } elseif (isset($params['all']) && $params['all'] === 'true') {
            $orders = $this->serviceOrderRepository->findAll($page, $limit);
        } else {
            $orders = $this->serviceOrderRepository->findActive($page, $limit);
        }

        $data = array_map(fn($order) => $this->serviceOrderToArray($order), $orders);

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
        $order = $this->serviceOrderRepository->findById($id);

        if (!$order) {
            $response->getBody()->write(json_encode(['error' => self::MSG_NOT_FOUND_SERVICE_ORDER]));
            return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON)->withStatus(404);
        }

        $response->getBody()->write(json_encode($this->serviceOrderToArray($order, true)));
        return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
    }

    public function create(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        try {
            if (empty($data['client_id']) || empty($data['vehicle_id'])) {
                throw new \InvalidArgumentException('Cliente e veículo são obrigatórios');
            }

            $client = $this->clientRepository->findById((int)$data['client_id']);
            if (!$client) {
                throw new \InvalidArgumentException('Cliente não encontrado');
            }

            $vehicle = $this->vehicleRepository->findById((int)$data['vehicle_id']);
            if (!$vehicle) {
                throw new \InvalidArgumentException('Veículo não encontrado');
            }

            $orderNumber = $this->serviceOrderRepository->nextOrderNumber();
            $order = new ServiceOrder(
                $orderNumber,
                $client,
                $vehicle,
                $data['description'] ?? null
            );

            if (isset($data['services']) && is_array($data['services'])) {
                foreach ($data['services'] as $serviceData) {
                    $service = $this->serviceRepository->findById((int)$serviceData['service_id']);
                    if ($service) {
                        $quantity = (int)($serviceData['quantity'] ?? 1);
                        $order->addService($service, $quantity);
                    }
                }
            }

            if (isset($data['parts']) && is_array($data['parts'])) {
                foreach ($data['parts'] as $partData) {
                    $part = $this->partRepository->findById((int)$partData['part_id']);
                    if ($part) {
                        $quantity = (int)$partData['quantity'];
                        $order->addPart($part, $quantity);

                        $this->validateStockUseCase->execute($order, $part, $quantity);
                    }
                }
            }

            $this->createServiceOrderUseCase->execute($order);
            $this->metrics->incOrdersCreated($order->getStatus()->value);

            $this->logger->info('Ordem de serviço criada', [
                'order_id' => $order->getId(),
                'order_number' => $order->getOrderNumber(),
                'client_id' => $order->getClient()->getId(),
                'vehicle_id' => $order->getVehicle()->getId(),
                'status' => $order->getStatus()->value,
            ]);

            $response->getBody()->write(json_encode($this->serviceOrderToArray($order, true)));
            return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON)->withStatus(201);
        } catch (\InvalidArgumentException | \DomainException $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON)->withStatus(400);
        }
    }

    public function updateStatus(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $order = $this->serviceOrderRepository->findById($id);

        if (!$order) {
            $response->getBody()->write(json_encode(['error' => self::MSG_NOT_FOUND_SERVICE_ORDER]));
            return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON)->withStatus(404);
        }

        $data = $request->getParsedBody();

        try {
            if (empty($data['status'])) {
                throw new \InvalidArgumentException('Status é obrigatório');
            }

            $newStatus = OrderStatus::from($data['status']);
            $notes = $data['diagnostic_notes'] ?? null;
            $previousStatus = $order->getStatus();

            $this->transitionStatusUseCase->execute($order, $newStatus, $notes);

            $this->logger->info('Status da ordem alterado', [
                'order_id' => $order->getId(),
                'order_number' => $order->getOrderNumber(),
                'previous_status' => $previousStatus->value,
                'new_status' => $newStatus->value,
                'diagnostic_notes' => $notes,
            ]);

            $response->getBody()->write(json_encode($this->serviceOrderToArray($order, true)));
            return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
        } catch (\InvalidArgumentException | \DomainException $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON)->withStatus(400);
        }
    }

    public function approveQuotation(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $order = $this->serviceOrderRepository->findById($id);

        if (!$order) {
            $response->getBody()->write(json_encode(['error' => self::MSG_NOT_FOUND_SERVICE_ORDER]));
            return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON)->withStatus(404);
        }

        try {
            $this->approveQuotationUseCase->execute($order);

            $this->logger->info('Orçamento da ordem aprovado', [
                'order_id' => $order->getId(),
                'order_number' => $order->getOrderNumber(),
                'client_id' => $order->getClient()->getId(),
            ]);

            $response->getBody()->write(json_encode($this->serviceOrderToArray($order, true)));
            return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
        } catch (\DomainException $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON)->withStatus(400);
        }
    }

    public function rejectQuotation(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $order = $this->serviceOrderRepository->findById($id);

        if (!$order) {
            $response->getBody()->write(json_encode(['error' => self::MSG_NOT_FOUND_SERVICE_ORDER]));
            return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON)->withStatus(404);
        }

        $data = $request->getParsedBody();

        try {
            $reason = $data['reason'] ?? 'Cliente rejeitou o orçamento';

            $this->rejectQuotationUseCase->execute($order, $reason);

            $this->logger->warning('Orçamento da ordem rejeitado', [
                'order_id' => $order->getId(),
                'order_number' => $order->getOrderNumber(),
                'client_id' => $order->getClient()->getId(),
                'reason' => $reason,
            ]);

            $response->getBody()->write(json_encode($this->serviceOrderToArray($order, true)));
            return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
        } catch (\DomainException $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON)->withStatus(400);
        }
    }

    public function getAverageExecutionTime(Request $request, Response $response): Response
    {
        $stats = $this->serviceOrderRepository->getAverageExecutionTimeByService();

        $response->getBody()->write(json_encode([
            'data' => $stats
        ]));

        return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
    }

    public function addService(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $order = $this->serviceOrderRepository->findById($id);

        if (!$order) {
            $response->getBody()->write(json_encode(['error' => self::MSG_NOT_FOUND_SERVICE_ORDER]));
            return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON)->withStatus(404);
        }

        $data = $request->getParsedBody();

        if (!isset($data['service_id']) || !isset($data['quantity'])) {
            $response->getBody()->write(json_encode(['error' => 'service_id e quantity são obrigatórios']));
            return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON)->withStatus(400);
        }

        $service = $this->serviceRepository->findById((int)$data['service_id']);

        if (!$service) {
            $response->getBody()->write(json_encode(['error' => 'Serviço não encontrado']));
            return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON)->withStatus(404);
        }

        try {
            $order->addService($service, (int)$data['quantity']);
            $this->serviceOrderRepository->save($order);

            $response->getBody()->write(json_encode($this->serviceOrderToArray($order, true)));
            return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
        } catch (\DomainException $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON)->withStatus(400);
        }
    }

    public function addPart(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $order = $this->serviceOrderRepository->findById($id);

        if (!$order) {
            $response->getBody()->write(json_encode(['error' => self::MSG_NOT_FOUND_SERVICE_ORDER]));
            return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON)->withStatus(404);
        }

        $data = $request->getParsedBody();

        if (!isset($data['part_id']) || !isset($data['quantity'])) {
            $response->getBody()->write(json_encode(['error' => 'part_id e quantity são obrigatórios']));
            return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON)->withStatus(400);
        }

        $part = $this->partRepository->findById((int)$data['part_id']);

        if (!$part) {
            $response->getBody()->write(json_encode(['error' => 'Peça não encontrada']));
            return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON)->withStatus(404);
        }

        try {
            $requestedQuantity = (int)$data['quantity'];

            $validation = $this->validateStockUseCase->execute($order, $part, $requestedQuantity);

            $order->addPart($part, $requestedQuantity);
            $this->serviceOrderRepository->save($order);

            if ($validation['warning']) {
                $orderData = $this->serviceOrderToArray($order, true);
                $orderData['warning'] = $validation['warning'];
                $orderData['part_name'] = $part->getName();
                $orderData['available_stock'] = $validation['available'];
                $orderData['requested_quantity'] = $validation['requested'];

                $response->getBody()->write(json_encode($orderData));
                return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON)->withStatus(200);
            }

            $response->getBody()->write(json_encode($this->serviceOrderToArray($order, true)));
            return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
        } catch (\DomainException $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON)->withStatus(400);
        }
    }

    public function removeService(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $serviceId = (int)$args['serviceId'];

        $order = $this->serviceOrderRepository->findById($id);

        if (!$order) {
            $response->getBody()->write(json_encode(['error' => self::MSG_NOT_FOUND_SERVICE_ORDER]));
            return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON)->withStatus(404);
        }

        try {
            $serviceToRemove = null;
            foreach ($order->getServices() as $orderService) {
                if ($orderService->getService()->getId() === $serviceId) {
                    $serviceToRemove = $orderService;
                    break;
                }
            }

            if (!$serviceToRemove) {
                $response->getBody()->write(json_encode(['error' => 'Serviço não encontrado na ordem']));
                return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON)->withStatus(404);
            }

            $order->removeService($serviceToRemove);
            $this->serviceOrderRepository->save($order);

            $response->getBody()->write(json_encode([
                'message' => 'Serviço removido com sucesso',
                'order' => $this->serviceOrderToArray($order, true)
            ]));
            return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
        } catch (\DomainException $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON)->withStatus(400);
        }
    }

    public function removePart(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $partId = (int)$args['partId'];

        $order = $this->serviceOrderRepository->findById($id);

        if (!$order) {
            $response->getBody()->write(json_encode(['error' => self::MSG_NOT_FOUND_SERVICE_ORDER]));
            return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON)->withStatus(404);
        }

        try {
            $partToRemove = null;
            foreach ($order->getParts() as $orderPart) {
                if ($orderPart->getPart()->getId() === $partId) {
                    $partToRemove = $orderPart;
                    break;
                }
            }

            if (!$partToRemove) {
                $response->getBody()->write(json_encode(['error' => 'Peça não encontrada na ordem']));
                return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON)->withStatus(404);
            }

            $order->removePart($partToRemove);
            $this->serviceOrderRepository->save($order);

            $response->getBody()->write(json_encode([
                'message' => 'Peça removida com sucesso',
                'order' => $this->serviceOrderToArray($order, true)
            ]));
            return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
        } catch (\DomainException $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON)->withStatus(400);
        }
    }

    private function serviceOrderToArray(ServiceOrder $order, bool $detailed = false): array
    {
        $data = [
            'id' => $order->getId(),
            'order_number' => $order->getOrderNumber(),
            'status' => $order->getStatus()->value,
            'status_label' => $order->getStatus()->getLabel(),
            'cancelled' => $order->isCancelled(),
            'client_id' => $order->getClient()->getId(),
            'vehicle_id' => $order->getVehicle()->getId(),
            'client' => [
                'id' => $order->getClient()->getId(),
                'name' => $order->getClient()->getName(),
            ],
            'vehicle' => [
                'id' => $order->getVehicle()->getId(),
                'plate' => $order->getVehicle()->getPlate()->getFormatted(),
                'model' => $order->getVehicle()->getModel(),
            ],
            'description' => $order->getDescription(),
            'quotation_total' => $order->getQuotationTotal()?->getFormatted(),
            'quotation_approved' => $order->isQuotationApproved(),
            'created_at' => $order->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $order->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];

        if ($detailed) {
            $data['services'] = array_map(function ($item) {
                return [
                    'id' => $item->getService()->getId(),
                    'name' => $item->getService()->getName(),
                    'quantity' => $item->getQuantity(),
                    'unit_price' => $item->getUnitPrice()->getFormatted(),
                    'subtotal' => $item->getSubtotal()->getFormatted(),
                ];
            }, $order->getServices()->toArray());

            $data['parts'] = array_map(function ($item) {
                return [
                    'id' => $item->getPart()->getId(),
                    'name' => $item->getPart()->getName(),
                    'quantity' => $item->getQuantity(),
                    'unit_price' => $item->getUnitPrice()->getFormatted(),
                    'subtotal' => $item->getSubtotal()->getFormatted(),
                ];
            }, $order->getParts()->toArray());

            $data['status_history'] = array_map(function ($history) {
                return [
                    'status' => $history->getStatus()->value,
                    'status_label' => $history->getStatus()->getLabel(),
                    'notes' => $history->getNotes(),
                    'changed_at' => $history->getChangedAt()->format('Y-m-d H:i:s'),
                ];
            }, $order->getStatusHistory()->toArray());

            $data['diagnostic_notes'] = $order->getDiagnosticNotes();
            $data['quotation_sent_at'] = $order->getQuotationSentAt()?->format('Y-m-d H:i:s');
            $data['quotation_approved_at'] = $order->getQuotationApprovedAt()?->format('Y-m-d H:i:s');
            $data['quotation_rejected_at'] = $order->getQuotationRejectedAt()?->format('Y-m-d H:i:s');
            $data['rejection_reason'] = $order->getRejectionReason();
            $data['reminder_sent_at'] = $order->getLastReminderSentAt()?->format('Y-m-d H:i:s');
            $data['started_at'] = $order->getStartedAt()?->format('Y-m-d H:i:s');
            $data['finished_at'] = $order->getFinishedAt()?->format('Y-m-d H:i:s');
            $data['delivered_at'] = $order->getDeliveredAt()?->format('Y-m-d H:i:s');
            $data['average_execution_time'] = $order->getAverageExecutionTime();
        }

        return $data;
    }
}
