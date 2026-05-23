<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Service;

interface ServiceRepositoryInterface
{
    public function findById(int $id): ?Service;

    public function findByName(string $name): ?Service;

    /**
     * @return array<int, Service>
     */
    public function findActive(): array;

    /**
     * @return array<int, Service>
     */
    public function findAll(int $page = 1, int $limit = 20): array;

    public function save(Service $service): void;

    public function delete(Service $service): void;
}
