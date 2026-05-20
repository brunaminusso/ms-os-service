<?php

declare(strict_types=1);

namespace App\Application\UseCase\ServiceOrder;

use App\Domain\Entity\ServiceOrder;
use App\Domain\Repository\ServiceOrderRepositoryInterface;
use App\Domain\Service\NotificationService;
use App\Domain\Service\PartReservationService;

class RejectQuotationUseCase
{
    public function __construct(
        private ServiceOrderRepositoryInterface $serviceOrderRepository,
        private NotificationService $notificationService,
        private PartReservationService $reservationService
    ) {}

    public function execute(ServiceOrder $order, string $reason): ServiceOrder
    {
        $order->rejectQuotation($reason);

        $order->cancel('Orçamento rejeitado pelo cliente: ' . $reason);

        $this->reservationService->cancelReservations($order);

        $this->serviceOrderRepository->save($order);

        $this->notificationService->notifyQuotationRejected($order);

        return $order;
    }
}
