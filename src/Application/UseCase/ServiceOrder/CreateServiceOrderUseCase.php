<?php

declare(strict_types=1);

namespace App\Application\UseCase\ServiceOrder;

use App\Domain\Entity\ServiceOrder;
use App\Domain\Repository\ServiceOrderRepositoryInterface;
use App\Domain\Service\NotificationService;
use App\Application\Saga\ServiceOrderSagaOrchestrator;

class CreateServiceOrderUseCase
{
    public function __construct(
        private ServiceOrderRepositoryInterface $serviceOrderRepository,
        private NotificationService $notificationService,
        private ServiceOrderSagaOrchestrator $orchestrator
    ) {}

    public function execute(ServiceOrder $order): ServiceOrder
    {
        $this->serviceOrderRepository->save($order);

        $this->notificationService->notifyServiceOrderCreated($order);

        try {
            $this->orchestrator->requestQuotation($order);
        } catch (\Throwable $exception) {
            $this->orchestrator->compensateQuotationFailure($order, $exception->getMessage());
        }

        return $order;
    }
}
