<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\ServiceOrder;
use App\Domain\Enum\OrderStatus;
use App\Domain\Repository\ServiceOrderRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class ServiceOrderRepository implements ServiceOrderRepositoryInterface
{
    private EntityRepository $repository;

    public function __construct(private EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(ServiceOrder::class);
    }

    public function findById(int $id): ?ServiceOrder
    {
        /** @var ServiceOrder|null */
        return $this->repository->find($id);
    }

    public function findByOrderNumber(string $orderNumber): ?ServiceOrder
    {
        /** @var ServiceOrder|null */
        return $this->repository->findOneBy(['orderNumber' => $orderNumber]);
    }

    public function findByClientId(int $clientId): array
    {
        return $this->repository->createQueryBuilder('so')
            ->where('so.client = :clientId')
            ->setParameter('clientId', $clientId)
            ->orderBy('so.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByVehicleId(int $vehicleId): array
    {
        return $this->repository->createQueryBuilder('so')
            ->where('so.vehicle = :vehicleId')
            ->setParameter('vehicleId', $vehicleId)
            ->orderBy('so.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(OrderStatus $status): array
    {
        return $this->repository->createQueryBuilder('so')
            ->where('so.status = :status')
            ->setParameter('status', $status)
            ->orderBy('so.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAll(int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;

        return $this->repository->createQueryBuilder('so')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy('so.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findActive(int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;

        return $this->repository->createQueryBuilder('so')
            ->where('so.status NOT IN (:excludedStatuses)')
            ->setParameter('excludedStatuses', ['finished', 'delivered'])
            ->addOrderBy(
                'CASE ' .
                    "WHEN so.status = 'in_execution' THEN 1 " .
                    "WHEN so.status = 'awaiting_approval' THEN 2 " .
                    "WHEN so.status = 'in_diagnostic' THEN 3 " .
                    "WHEN so.status = 'received' THEN 4 " .
                    'ELSE 5 END'
            )
            ->addOrderBy('so.createdAt', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getAverageExecutionTimeByService(): array
    {
        $conn = $this->entityManager->getConnection();
        $sql = '
            SELECT s.name, AVG(TIMESTAMPDIFF(SECOND, so.started_at, so.finished_at)) as avgTime
            FROM service_orders so
            JOIN service_order_services sos ON sos.service_order_id = so.id
            JOIN services s ON s.id = sos.service_id
            WHERE so.started_at IS NOT NULL
            AND so.finished_at IS NOT NULL
            GROUP BY s.id, s.name
        ';

        return $conn->fetchAllAssociative($sql);
    }

    public function save(ServiceOrder $serviceOrder): void
    {
        $this->entityManager->persist($serviceOrder);
        $this->entityManager->flush();
    }

    public function delete(ServiceOrder $serviceOrder): void
    {
        $this->entityManager->remove($serviceOrder);
        $this->entityManager->flush();
    }

    public function nextOrderNumber(): string
    {
        $lastOrder = $this->repository->createQueryBuilder('so')
            ->orderBy('so.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$lastOrder) {
            return 'OS-' . date('Y') . '-0001';
        }

        preg_match('/-(\d+)$/', $lastOrder->getOrderNumber(), $matches);
        $nextNumber = isset($matches[1]) ? (int)$matches[1] + 1 : 1;

        return 'OS-' . date('Y') . '-' . str_pad((string)$nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
