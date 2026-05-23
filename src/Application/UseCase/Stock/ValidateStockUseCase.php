<?php

declare(strict_types=1);

namespace App\Application\UseCase\Stock;

use App\Domain\Entity\Part;
use App\Domain\Entity\ServiceOrder;
use App\Domain\Service\NotificationService;

class ValidateStockUseCase
{
    public function __construct(
        private NotificationService $notificationService
    ) {
    }

    /**
     * @return array<string,mixed>
     */
    public function execute(
        ServiceOrder $order,
        Part $part,
        int $requestedQuantity
    ): array {
        $available = $part->getStockQuantity();
        $sufficient = $available >= $requestedQuantity;

        $result = [
            'sufficient' => $sufficient,
            'available' => $available,
            'requested' => $requestedQuantity,
            'warning' => null
        ];

        if (!$sufficient) {
            $this->notificationService->notifyPartUnavailable($order, $part->getName());

            $result['warning'] = sprintf(
                'Peça "%s" com estoque insuficiente. Disponível: %d, Solicitado: %d. Cliente foi notificado.',
                $part->getName(),
                $available,
                $requestedQuantity
            );
        }

        return $result;
    }

    public function hasAvailability(Part $part, int $requestedQuantity): bool
    {
        return $part->getStockQuantity() >= $requestedQuantity;
    }

    public function getAvailableQuantity(Part $part): int
    {
        return $part->getStockQuantity();
    }
}
