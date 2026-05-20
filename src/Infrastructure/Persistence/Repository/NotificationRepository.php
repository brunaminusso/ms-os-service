<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\Notification;
use App\Domain\Repository\NotificationRepositoryInterface;
use Doctrine\ORM\EntityManager;

class NotificationRepository implements NotificationRepositoryInterface
{
    public function __construct(private EntityManager $entityManager)
    {
    }

    public function save(Notification $notification): void
    {
        $this->entityManager->persist($notification);
        $this->entityManager->flush();
    }

    public function findById(int $id): ?Notification
    {
        return $this->entityManager->find(Notification::class, $id);
    }

    public function findAll(int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;

        return $this->entityManager
            ->getRepository(Notification::class)
            ->findBy([], ['createdAt' => 'DESC'], $limit, $offset);
    }

    public function findByClient(int $clientId, int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;

        return $this->entityManager
            ->createQueryBuilder()
            ->select('n')
            ->from(Notification::class, 'n')
            ->where('n.client = :clientId')
            ->setParameter('clientId', $clientId)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function findByServiceOrder(int $serviceOrderId): array
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('n')
            ->from(Notification::class, 'n')
            ->where('n.serviceOrder = :serviceOrderId')
            ->setParameter('serviceOrderId', $serviceOrderId)
            ->orderBy('n.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findPending(): array
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('n')
            ->from(Notification::class, 'n')
            ->where('n.status = :status')
            ->setParameter('status', 'pending')
            ->orderBy('n.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
