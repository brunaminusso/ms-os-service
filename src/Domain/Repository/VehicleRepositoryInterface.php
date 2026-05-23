<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Vehicle;

interface VehicleRepositoryInterface
{
    public function findById(int $id): ?Vehicle;

    public function findByPlate(string $plate): ?Vehicle;

    /**
     * @return array<int, Vehicle>
     */
    public function findByClientId(int $clientId): array;

    /**
     * @return array<int, Vehicle>
     */
    public function findAll(int $page = 1, int $limit = 20): array;

    public function save(Vehicle $vehicle): void;

    public function delete(Vehicle $vehicle): void;
}
