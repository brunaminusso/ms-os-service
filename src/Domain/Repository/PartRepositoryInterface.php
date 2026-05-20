<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Part;

interface PartRepositoryInterface
{
    public function findById(int $id): ?Part;

    public function findByCode(string $code): ?Part;

    public function findLowStock(): array;

    public function findActive(): array;

    public function findAll(int $page = 1, int $limit = 20): array;

    public function save(Part $part): void;

    public function delete(Part $part): void;
}
