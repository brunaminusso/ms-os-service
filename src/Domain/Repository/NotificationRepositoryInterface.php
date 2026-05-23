<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Notification;

interface NotificationRepositoryInterface
{
    public function save(Notification $notification): void;

    public function findById(int $id): ?Notification;

    /**
     * @return array<int, Notification>
     */
    public function findAll(int $page = 1, int $limit = 20): array;

    /**
     * @return array<int, Notification>
     */
    public function findByClient(int $clientId, int $page = 1, int $limit = 20): array;

    /**
     * @return array<int, Notification>
     */
    public function findByServiceOrder(int $serviceOrderId): array;

    /**
     * @return array<int, Notification>
     */
    public function findPending(): array;
}
