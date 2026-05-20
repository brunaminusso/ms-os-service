<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\Client;
use App\Domain\Repository\ClientRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class ClientRepository implements ClientRepositoryInterface
{
    private EntityRepository $repository;

    public function __construct(private EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(Client::class);
    }

    public function findById(int $id): ?Client
    {
        /** @var Client|null */
        return $this->repository->find($id);
    }

    public function findByCpf(string $cpf): ?Client
    {
        /** @var Client|null */
        return $this->repository->findOneBy(['cpf' => $cpf]);
    }

    public function findByCnpj(string $cnpj): ?Client
    {
        /** @var Client|null */
        return $this->repository->findOneBy(['cnpj' => $cnpj]);
    }

    public function findByEmail(string $email): ?Client
    {
        /** @var Client|null */
        return $this->repository->findOneBy(['email' => $email]);
    }

    public function findAll(int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;

        return $this->repository->createQueryBuilder('c')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(Client $client): void
    {
        $this->entityManager->persist($client);
        $this->entityManager->flush();
    }

    public function delete(Client $client): void
    {
        $this->entityManager->remove($client);
        $this->entityManager->flush();
    }
}
