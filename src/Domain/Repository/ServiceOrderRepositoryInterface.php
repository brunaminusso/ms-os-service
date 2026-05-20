<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\ServiceOrder;
use App\Domain\Enum\OrderStatus;

interface ServiceOrderRepositoryInterface
{
    public function findById(int $id): ?ServiceOrder;

    public function findByOrderNumber(string $orderNumber): ?ServiceOrder;

    public function findByClientId(int $clientId): array;

    public function findByVehicleId(int $vehicleId): array;

    public function findByStatus(OrderStatus $status): array;

    public function findAll(int $page = 1, int $limit = 20): array;

    public function findActive(int $page = 1, int $limit = 20): array;

    public function getAverageExecutionTimeByService(): array;

    public function save(ServiceOrder $serviceOrder): void;

    public function delete(ServiceOrder $serviceOrder): void;

    public function nextOrderNumber(): string;
}
