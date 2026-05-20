<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\Service;
use App\Domain\Repository\ServiceRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class ServiceRepository implements ServiceRepositoryInterface
{
    private EntityRepository $repository;

    public function __construct(private EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(Service::class);
    }

    public function findById(int $id): ?Service
    {
        /** @var Service|null */
        return $this->repository->find($id);
    }

    public function findByName(string $name): ?Service
    {
        /** @var Service|null */
        return $this->repository->findOneBy(['name' => $name]);
    }

    public function findActive(): array
    {
        return $this->repository->findBy(['active' => true], ['name' => 'ASC']);
    }

    public function findAll(int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;

        return $this->repository->createQueryBuilder('s')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(Service $service): void
    {
        $this->entityManager->persist($service);
        $this->entityManager->flush();
    }

    public function delete(Service $service): void
    {
        $this->entityManager->remove($service);
        $this->entityManager->flush();
    }
}
