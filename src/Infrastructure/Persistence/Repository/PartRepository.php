<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\Part;
use App\Domain\Repository\PartRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class PartRepository implements PartRepositoryInterface
{
    private EntityRepository $repository;

    public function __construct(private EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(Part::class);
    }

    public function findById(int $id): ?Part
    {
        /** @var Part|null */
        return $this->repository->find($id);
    }

    public function findByCode(string $code): ?Part
    {
        /** @var Part|null */
        return $this->repository->findOneBy(['code' => $code]);
    }

    public function findLowStock(): array
    {
        return $this->repository->createQueryBuilder('p')
            ->where('p.minimumStock IS NOT NULL')
            ->andWhere('p.stockQuantity <= p.minimumStock')
            ->andWhere('p.active = true')
            ->orderBy('p.stockQuantity', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findActive(): array
    {
        return $this->repository->findBy(['active' => true], ['name' => 'ASC']);
    }

    public function findAll(int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;

        return $this->repository->createQueryBuilder('p')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(Part $part): void
    {
        $this->entityManager->persist($part);
        $this->entityManager->flush();
    }

    public function delete(Part $part): void
    {
        $this->entityManager->remove($part);
        $this->entityManager->flush();
    }
}
