<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\PartReservation;
use App\Domain\Entity\ServiceOrder;

interface PartReservationRepositoryInterface
{
    public function save(PartReservation $reservation): void;

    public function findById(int $id): ?PartReservation;

    public function findByServiceOrder(ServiceOrder $order): array;

    public function findExpired(): array;

    public function findReserved(): array;
}
