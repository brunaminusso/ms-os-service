<?php

declare(strict_types=1);

namespace Tests\Application\Saga;

use App\Application\Saga\ServiceOrderSagaOrchestrator;
use App\Application\UseCase\ServiceOrder\TransitionStatusUseCase;
use App\Domain\Entity\Client;
use App\Domain\Entity\ServiceOrder;
use App\Domain\Entity\Vehicle;
use App\Domain\Enum\OrderStatus;
use App\Domain\Repository\ServiceOrderRepositoryInterface;
use App\Domain\Service\NotificationService;
use App\Domain\Service\PartReservationService;
use App\Domain\ValueObject\VehiclePlate;
use App\Infrastructure\Messaging\RabbitMqCommandPublisher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ServiceOrderSagaFlowBddTest extends TestCase
{
    private RabbitMqCommandPublisher|MockObject $publisher;
    private ServiceOrderRepositoryInterface|MockObject $repository;
    private NotificationService|MockObject $notificationService;
    private PartReservationService|MockObject $reservationService;

    protected function setUp(): void
    {
        $this->publisher = $this->createMock(RabbitMqCommandPublisher::class);
        $this->repository = $this->createMock(ServiceOrderRepositoryInterface::class);
        $this->notificationService = $this->createMock(NotificationService::class);
        $this->reservationService = $this->createMock(PartReservationService::class);
    }

    private function makeOrder(int $id = 101): ServiceOrder
    {
        $client = new Client('Cliente BDD');
        $vehicle = new Vehicle(new VehiclePlate('ABC1234'), 'Ford', 'Ka', 2020, $client);
        $order = new ServiceOrder('OS-101', $client, $vehicle, 'Fluxo BDD');

        $ref = new \ReflectionProperty(ServiceOrder::class, 'id');
        $ref->setAccessible(true);
        $ref->setValue($order, $id);

        return $order;
    }

    public function testGivenOrderWhenQuotationApprovedThenExecutionStarts(): void
    {
        $order = $this->makeOrder(99);

        $this->publisher
            ->expects($this->exactly(2))
            ->method('publish')
            ->withConsecutive(
                [
                    'billing.commands',
                    'billing.quotation.create',
                    $this->callback(fn (array $payload) => $payload['service_order_id'] === 99),
                ],
                [
                    'execution.commands',
                    'execution.start',
                    $this->callback(fn (array $payload) => $payload['service_order_id'] === 99),
                ]
            );

        $this->reservationService->expects($this->once())->method('createReservationsForOrder')->with($order);
        $this->notificationService->expects($this->once())->method('notifyQuotationCreated')->with($order);
        $this->notificationService->expects($this->exactly(3))->method('notifyStatusChanged')->with($order, $this->anything());
        $this->repository->expects($this->exactly(3))->method('save')->with($order);

        $orchestrator = new ServiceOrderSagaOrchestrator(
            $this->publisher,
            $this->repository,
            $this->notificationService,
            $this->reservationService
        );

        $statusUseCase = new TransitionStatusUseCase(
            $this->repository,
            $this->notificationService,
            $this->reservationService
        );

        $orchestrator->requestQuotation($order);
        $statusUseCase->execute($order, OrderStatus::IN_DIAGNOSTIC, 'Diagnostico iniciado');
        $statusUseCase->execute($order, OrderStatus::AWAITING_APPROVAL, 'Orcamento gerado pelo Billing Service');
        $orchestrator->startExecution($order, [
            'payment_id' => 1,
            'provider_payment_id' => 'mp-1',
            'quotation_id' => 10,
        ]);
        $statusUseCase->execute($order, OrderStatus::IN_EXECUTION, 'Atualizado pelo Execution Service');
    }
}
