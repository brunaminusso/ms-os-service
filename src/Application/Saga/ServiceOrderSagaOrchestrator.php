<?php

declare(strict_types=1);

namespace App\Application\Saga;

use App\Domain\Entity\ServiceOrder;
use App\Domain\Repository\ServiceOrderRepositoryInterface;
use App\Domain\Service\NotificationService;
use App\Domain\Service\PartReservationService;
use App\Infrastructure\Messaging\RabbitMqCommandPublisher;

class ServiceOrderSagaOrchestrator
{
    public function __construct(
        private RabbitMqCommandPublisher $publisher,
        private ServiceOrderRepositoryInterface $serviceOrderRepository,
        private NotificationService $notificationService,
        private PartReservationService $reservationService
    ) {
    }

    public function requestQuotation(ServiceOrder $order): void
    {
        $total = $order->getQuotationTotal();
        $payload = [
            'service_order_id' => $order->getId(),
            'order_number' => $order->getOrderNumber(),
            'total_in_cents' => $total ? $total->getAmountInCents() : 0,
            'currency' => $total ? $total->getCurrency() : 'BRL',
        ];

        $this->publisher->publish('billing.commands', 'billing.quotation.create', $payload);
    }

    /**
     * @param array<string,mixed> $paymentPayload
     */
    public function startExecution(ServiceOrder $order, array $paymentPayload): void
    {
        $payload = [
            'service_order_id' => $order->getId(),
            'stage' => 'in_execution',
            'metadata' => [
                'payment_id' => $paymentPayload['payment_id'] ?? null,
                'provider_payment_id' => $paymentPayload['provider_payment_id'] ?? null,
                'quotation_id' => $paymentPayload['quotation_id'] ?? null,
            ],
        ];

        $this->publisher->publish('execution.commands', 'execution.start', $payload);
    }

    public function compensateQuotationFailure(ServiceOrder $order, string $reason): void
    {
        $order->cancel('Falha ao gerar orcamento: ' . $reason);
        $this->reservationService->cancelReservations($order);
        $this->serviceOrderRepository->save($order);
        $this->notificationService->notifyOrderCancelled($order, $reason);
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function compensateExecutionFailure(ServiceOrder $order, string $reason, array $payload): void
    {
        $order->cancel('Falha na execucao: ' . $reason);
        $this->reservationService->cancelReservations($order);
        $this->serviceOrderRepository->save($order);
        $this->notificationService->notifyOrderCancelled($order, $reason);

        $providerPaymentId = $payload['provider_payment_id'] ?? null;
        if ($providerPaymentId) {
            $this->publisher->publish('billing.commands', 'billing.payment.refund.requested', [
                'service_order_id' => $order->getId(),
                'provider_payment_id' => $providerPaymentId,
                'payment_id' => $payload['payment_id'] ?? null,
                'reason' => $reason,
            ]);
        }
    }

    public function compensatePaymentFailure(ServiceOrder $order, string $reason): void
    {
        $order->cancel('Falha no pagamento: ' . $reason);
        $this->reservationService->cancelReservations($order);
        $this->serviceOrderRepository->save($order);
        $this->notificationService->notifyOrderCancelled($order, $reason);
    }

    public function handleRefundFailure(ServiceOrder $order, string $reason): void
    {
        $this->notificationService->notifyOrderCancelled($order, 'Reembolso falhou: ' . $reason);
    }
}
