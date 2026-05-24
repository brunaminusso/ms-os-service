<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Repository;

use App\Domain\Entity\Client;
use App\Domain\Entity\Notification;
use App\Infrastructure\Persistence\Repository\NotificationRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use stdClass;

final class NotificationRepositoryTest extends TestCase
{
    public function testSavePersistsAndFlushesNotification(): void
    {
        $notification = new Notification(
            $this->createMock(Client::class),
            'email',
            'service_order.created',
            'OS criada',
            'Detalhes da ordem'
        );

        $entityManager = $this->createEntityManagerMock();
        $entityManager->expects($this->once())->method('persist')->with($notification);
        $entityManager->expects($this->once())->method('flush');

        $repository = new NotificationRepository($entityManager);
        $repository->save($notification);
    }

    public function testFindByIdDelegatesToEntityManager(): void
    {
        $notification = $this->createMock(Notification::class);

        $entityManager = $this->createEntityManagerMock();
        $entityManager->expects($this->once())
            ->method('find')
            ->with(Notification::class, 15)
            ->willReturn($notification);

        $repository = new NotificationRepository($entityManager);

        $this->assertSame($notification, $repository->findById(15));
    }

    public function testFindAllUsesRepositoryPagination(): void
    {
        $expected = [$this->createMock(Notification::class)];

        $doctrineRepository = $this->createMock(EntityRepository::class);
        $doctrineRepository->expects($this->once())
            ->method('findBy')
            ->with([], ['createdAt' => 'DESC'], 10, 10)
            ->willReturn($expected);

        $entityManager = $this->createEntityManagerMock();
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with(Notification::class)
            ->willReturn($doctrineRepository);

        $repository = new NotificationRepository($entityManager);

        $this->assertSame($expected, $repository->findAll(2, 10));
    }

    public function testFindByClientBuildsExpectedQuery(): void
    {
        $expected = [$this->createMock(Notification::class)];
        $query = $this->createQueryMock($expected);

        $queryBuilder = $this->getMockBuilder(stdClass::class)
            ->addMethods([
                'select',
                'from',
                'where',
                'setParameter',
                'orderBy',
                'setMaxResults',
                'setFirstResult',
                'getQuery',
            ])
            ->getMock();

        $queryBuilder->expects($this->once())->method('select')->with('n')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('from')->with(Notification::class, 'n')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('where')->with('n.client = :clientId')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('setParameter')->with('clientId', 8)->willReturnSelf();
        $queryBuilder->expects($this->once())->method('orderBy')->with('n.createdAt', 'DESC')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('setMaxResults')->with(5)->willReturnSelf();
        $queryBuilder->expects($this->once())->method('setFirstResult')->with(5)->willReturnSelf();
        $queryBuilder->expects($this->once())->method('getQuery')->willReturn($query);

        $entityManager = $this->createEntityManagerMock();
        $entityManager->expects($this->once())->method('createQueryBuilder')->willReturn($queryBuilder);

        $repository = new NotificationRepository($entityManager);

        $this->assertSame($expected, $repository->findByClient(8, 2, 5));
    }

    public function testFindByServiceOrderBuildsExpectedQuery(): void
    {
        $expected = [$this->createMock(Notification::class)];
        $query = $this->createQueryMock($expected);

        $queryBuilder = $this->getMockBuilder(stdClass::class)
            ->addMethods(['select', 'from', 'where', 'setParameter', 'orderBy', 'getQuery'])
            ->getMock();

        $queryBuilder->expects($this->once())->method('select')->with('n')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('from')->with(Notification::class, 'n')->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('where')
            ->with('n.serviceOrder = :serviceOrderId')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())->method('setParameter')->with('serviceOrderId', 21)->willReturnSelf();
        $queryBuilder->expects($this->once())->method('orderBy')->with('n.createdAt', 'ASC')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('getQuery')->willReturn($query);

        $entityManager = $this->createEntityManagerMock();
        $entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $repository = new NotificationRepository($entityManager);

        $this->assertSame($expected, $repository->findByServiceOrder(21));
    }

    public function testFindPendingBuildsExpectedQuery(): void
    {
        $expected = [$this->createMock(Notification::class)];
        $query = $this->createQueryMock($expected);

        $queryBuilder = $this->getMockBuilder(stdClass::class)
            ->addMethods(['select', 'from', 'where', 'setParameter', 'orderBy', 'getQuery'])
            ->getMock();

        $queryBuilder->expects($this->once())->method('select')->with('n')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('from')->with(Notification::class, 'n')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('where')->with('n.status = :status')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('setParameter')->with('status', 'pending')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('orderBy')->with('n.createdAt', 'ASC')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('getQuery')->willReturn($query);

        $entityManager = $this->createEntityManagerMock();
        $entityManager->expects($this->once())->method('createQueryBuilder')->willReturn($queryBuilder);

        $repository = new NotificationRepository($entityManager);

        $this->assertSame($expected, $repository->findPending());
    }

    private function createEntityManagerMock(): EntityManager
    {
        return $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['persist', 'flush', 'find', 'getRepository', 'createQueryBuilder'])
            ->getMock();
    }

    private function createQueryMock(array $result): object
    {
        $query = $this->getMockBuilder(stdClass::class)
            ->addMethods(['getResult'])
            ->getMock();
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($result);

        return $query;
    }
}
