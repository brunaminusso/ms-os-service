<?php

declare(strict_types=1);

namespace App\Application\UseCase\ServiceOrder;

use App\Domain\Entity\ServiceOrder;
use App\Domain\Enum\OrderStatus;
use App\Domain\Repository\ServiceOrderRepositoryInterface;
use App\Domain\Service\NotificationService;
use App\Domain\Service\PartReservationService;

class TransitionStatusUseCase
{
    public function __construct(
        private ServiceOrderRepositoryInterface $serviceOrderRepository,
        private NotificationService $notificationService,
        private PartReservationService $reservationService
    ) {
    }

    public function execute(
        ServiceOrder $order,
        OrderStatus $newStatus,
        ?string $notes = null
    ): ServiceOrder {
        $previousStatus = $order->getStatus()->value;

        $order->changeStatus($newStatus);

        if ($notes) {
            $order->setDiagnosticNotes($notes);
        }

        $this->executeStatusSpecificActions($order, $newStatus);

        $this->serviceOrderRepository->save($order);

        $this->notificationService->notifyStatusChanged($order, $previousStatus);

        return $order;
    }

    private function executeStatusSpecificActions(ServiceOrder $order, OrderStatus $status): void
    {
        match ($status) {
            OrderStatus::AWAITING_APPROVAL => $this->handleQuotationCreated($order),
            OrderStatus::IN_EXECUTION => $this->handleServiceStarted($order),
            OrderStatus::FINISHED => $this->handleServiceFinished($order),
            OrderStatus::DELIVERED => $this->handleVehicleDelivered($order),
            default => null
        };
    }

    private function handleQuotationCreated(ServiceOrder $order): void
    {
        $order->sendQuotation();

        $this->reservationService->createReservationsForOrder($order);

        $this->notificationService->notifyQuotationCreated($order);
    }

    private function handleServiceStarted(ServiceOrder $order): void
    {
        // Timestamp já é definido no changeStatus()
        // Notificação é enviada automaticamente
    }

    private function handleServiceFinished(ServiceOrder $order): void
    {
        // Timestamp já é definido no changeStatus()
        // Notificação é enviada automaticamente
    }

    private function handleVehicleDelivered(ServiceOrder $order): void
    {
        // Timestamp já é definido no changeStatus()
        // Notificação é enviada automaticamente
    }
}
