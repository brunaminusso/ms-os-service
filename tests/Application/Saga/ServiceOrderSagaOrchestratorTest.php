<?php

declare(strict_types=1);

namespace Tests\Application\Saga;

use App\Application\Saga\ServiceOrderSagaOrchestrator;
use App\Domain\Entity\Client;
use App\Domain\Entity\ServiceOrder;
use App\Domain\Entity\Vehicle;
use App\Domain\Repository\ServiceOrderRepositoryInterface;
use App\Domain\Service\NotificationService;
use App\Domain\Service\PartReservationService;
use App\Domain\ValueObject\VehiclePlate;
use App\Infrastructure\Messaging\RabbitMqCommandPublisher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ServiceOrderSagaOrchestratorTest extends TestCase
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

    private function makeOrder(int $id = 10): ServiceOrder
    {
        $client = new Client('Cliente Teste');
        $vehicle = new Vehicle(new VehiclePlate('ABC1234'), 'Ford', 'Ka', 2020, $client);
        $order = new ServiceOrder('OS-100', $client, $vehicle, 'Troca de oleo');

        $ref = new \ReflectionProperty(ServiceOrder::class, 'id');
        $ref->setAccessible(true);
        $ref->setValue($order, $id);

        return $order;
    }

    public function testRequestQuotationPublishesCommand(): void
    {
        $order = $this->makeOrder(42);

        $this->publisher
            ->expects($this->once())
            ->method('publish')
            ->with(
                'billing.commands',
                'billing.quotation.create',
                $this->callback(function (array $payload) {
                    return $payload['service_order_id'] === 42
                        && $payload['order_number'] === 'OS-100'
                        && $payload['currency'] === 'BRL';
                })
            );

        $orchestrator = new ServiceOrderSagaOrchestrator(
            $this->publisher,
            $this->repository,
            $this->notificationService,
            $this->reservationService
        );

        $orchestrator->requestQuotation($order);
    }

    public function testStartExecutionPublishesCommand(): void
    {
        $order = $this->makeOrder(55);

        $this->publisher
            ->expects($this->once())
            ->method('publish')
            ->with(
                'execution.commands',
                'execution.start',
                $this->callback(function (array $payload) {
                    return $payload['service_order_id'] === 55
                        && $payload['stage'] === 'in_execution'
                        && $payload['metadata']['payment_id'] === 1;
                })
            );

        $orchestrator = new ServiceOrderSagaOrchestrator(
            $this->publisher,
            $this->repository,
            $this->notificationService,
            $this->reservationService
        );

        $orchestrator->startExecution($order, [
            'payment_id' => 1,
            'provider_payment_id' => 'mp-1',
            'quotation_id' => 9,
        ]);
    }

    public function testCompensatePaymentFailureCancelsOrder(): void
    {
        $order = $this->makeOrder(7);

        $this->reservationService->expects($this->once())->method('cancelReservations')->with($order);
        $this->repository->expects($this->once())->method('save')->with($order);
        $this->notificationService->expects($this->once())
            ->method('notifyOrderCancelled')
            ->with($order, $this->stringContains('Pagamento recusado'));

        $orchestrator = new ServiceOrderSagaOrchestrator(
            $this->publisher,
            $this->repository,
            $this->notificationService,
            $this->reservationService
        );

        $orchestrator->compensatePaymentFailure($order, 'Pagamento recusado');
    }

    public function testHandleRefundFailureNotifies(): void
    {
        $order = $this->makeOrder(8);

        $this->notificationService->expects($this->once())
            ->method('notifyOrderCancelled')
            ->with($order, $this->stringContains('Reembolso falhou'));

        $orchestrator = new ServiceOrderSagaOrchestrator(
            $this->publisher,
            $this->repository,
            $this->notificationService,
            $this->reservationService
        );

        $orchestrator->handleRefundFailure($order, 'Gateway indisponivel');
    }
}
