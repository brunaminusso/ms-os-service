<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\Vehicle;
use App\Domain\Repository\VehicleRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class VehicleRepository implements VehicleRepositoryInterface
{
    private EntityRepository $repository;

    public function __construct(private EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(Vehicle::class);
    }

    public function findById(int $id): ?Vehicle
    {
        /** @var Vehicle|null */
        return $this->repository->find($id);
    }

    public function findByPlate(string $plate): ?Vehicle
    {
        /** @var Vehicle|null */
        return $this->repository->findOneBy(['plate' => $plate]);
    }

    public function findByClientId(int $clientId): array
    {
        return $this->repository->createQueryBuilder('v')
            ->where('v.client = :clientId')
            ->setParameter('clientId', $clientId)
            ->orderBy('v.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAll(int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;

        return $this->repository->createQueryBuilder('v')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy('v.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(Vehicle $vehicle): void
    {
        $this->entityManager->persist($vehicle);
        $this->entityManager->flush();
    }

    public function delete(Vehicle $vehicle): void
    {
        $this->entityManager->remove($vehicle);
        $this->entityManager->flush();
    }
}
