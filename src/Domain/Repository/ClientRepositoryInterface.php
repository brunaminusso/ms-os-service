<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Client;

interface ClientRepositoryInterface
{
    public function findById(int $id): ?Client;

    public function findByCpf(string $cpf): ?Client;

    public function findByCnpj(string $cnpj): ?Client;

    public function findByEmail(string $email): ?Client;

    /**
     * @return array<int, Client>
     */
    public function findAll(int $page = 1, int $limit = 20): array;

    public function save(Client $client): void;

    public function delete(Client $client): void;
}
