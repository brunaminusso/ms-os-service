<?php

declare(strict_types=1);

namespace App\Application\UseCase\ServiceOrder;

use App\Domain\Entity\ServiceOrder;
use App\Domain\Enum\OrderStatus;
use App\Domain\Repository\ServiceOrderRepositoryInterface;
use App\Domain\Service\NotificationService;
use App\Domain\Service\PartReservationService;

class ApproveQuotationUseCase
{
    public function __construct(
        private ServiceOrderRepositoryInterface $serviceOrderRepository,
        private NotificationService $notificationService,
        private PartReservationService $reservationService
    ) {}

    public function execute(ServiceOrder $order): ServiceOrder
    {
        $order->approveQuotation();

        $order->changeStatus(OrderStatus::IN_EXECUTION);

        $this->reservationService->confirmReservations($order);

        $this->serviceOrderRepository->save($order);

        $this->notificationService->notifyQuotationApproved($order);
        $this->notificationService->notifyStatusChanged($order, 'awaiting_approval');

        return $order;
    }
}
