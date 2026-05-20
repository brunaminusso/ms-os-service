<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\PartReservation;
use App\Domain\Entity\ServiceOrder;
use App\Domain\Repository\PartReservationRepositoryInterface;
use Doctrine\ORM\EntityManager;

class PartReservationRepository implements PartReservationRepositoryInterface
{
    public function __construct(private EntityManager $entityManager)
    {
    }

    public function save(PartReservation $reservation): void
    {
        $this->entityManager->persist($reservation);
        $this->entityManager->flush();
    }

    public function findById(int $id): ?PartReservation
    {
        return $this->entityManager->find(PartReservation::class, $id);
    }

    public function findByServiceOrder(ServiceOrder $order): array
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('pr')
            ->from(PartReservation::class, 'pr')
            ->where('pr.serviceOrder = :order')
            ->setParameter('order', $order)
            ->getQuery()
            ->getResult();
    }

    public function findExpired(): array
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('pr')
            ->from(PartReservation::class, 'pr')
            ->where('pr.status = :status')
            ->andWhere('pr.expiresAt < :now')
            ->setParameter('status', 'reserved')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getResult();
    }

    public function findReserved(): array
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('pr')
            ->from(PartReservation::class, 'pr')
            ->where('pr.status = :status')
            ->setParameter('status', 'reserved')
            ->getQuery()
            ->getResult();
    }
}
