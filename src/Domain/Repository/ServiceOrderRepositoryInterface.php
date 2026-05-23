<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\ServiceOrder;
use App\Domain\Enum\OrderStatus;

interface ServiceOrderRepositoryInterface
{
    public function findById(int $id): ?ServiceOrder;

    public function findByOrderNumber(string $orderNumber): ?ServiceOrder;

    /**
     * @return array<int, ServiceOrder>
     */
    public function findByClientId(int $clientId): array;

    /**
     * @return array<int, ServiceOrder>
     */
    public function findByVehicleId(int $vehicleId): array;

    /**
     * @return array<int, ServiceOrder>
     */
    public function findByStatus(OrderStatus $status): array;

    /**
     * @return array<int, ServiceOrder>
     */
    public function findAll(int $page = 1, int $limit = 20): array;

    /**
     * @return array<int, ServiceOrder>
     */
    public function findActive(int $page = 1, int $limit = 20): array;

    /**
     * @return array<int, array<string,mixed>>
     */
    public function getAverageExecutionTimeByService(): array;

    public function save(ServiceOrder $serviceOrder): void;

    public function delete(ServiceOrder $serviceOrder): void;

    public function nextOrderNumber(): string;
}
