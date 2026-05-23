<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Repository;

use App\Domain\Entity\ServiceOrder;
use App\Infrastructure\Persistence\Repository\ServiceOrderRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

final class ServiceOrderRepositoryTest extends TestCase
{
    public function testFindByIdDelegatesToRepository(): void
    {
        $order = $this->createMock(ServiceOrder::class);

        $repoMock = $this->createMock(EntityRepository::class);
        $repoMock->expects($this->once())->method('find')->with(123)->willReturn($order);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repoMock);

        $repository = new ServiceOrderRepository($em);
        $this->assertSame($order, $repository->findById(123));
    }

    public function testNextOrderNumberWhenNoLastOrderReturnsPattern(): void
    {
        $repoMock = $this->createMock(EntityRepository::class);

        $query = $this->getMockBuilder(stdClass::class)->setMethods(['getOneOrNullResult'])->getMock();
        $query->expects($this->once())->method('getOneOrNullResult')->willReturn(null);

        $qb = $this->getMockBuilder(stdClass::class)
            ->setMethods(['orderBy','setMaxResults','getQuery'])
            ->getMock();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $repoMock->method('createQueryBuilder')->willReturn($qb);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repoMock);

        $repository = new ServiceOrderRepository($em);
        $result = $repository->nextOrderNumber();

        $this->assertStringStartsWith('OS-' . date('Y') . '-', $result);
    }

    public function testNextOrderNumberIncrementsFromLast(): void
    {
        $last = $this->createMock(ServiceOrder::class);
        $last->method('getOrderNumber')->willReturn('OS-' . date('Y') . '-0010');

        $query = $this->getMockBuilder(stdClass::class)->setMethods(['getOneOrNullResult'])->getMock();
        $query->expects($this->once())->method('getOneOrNullResult')->willReturn($last);

        $qb = $this->getMockBuilder(stdClass::class)
            ->setMethods(['orderBy','setMaxResults','getQuery'])
            ->getMock();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $repoMock = $this->createMock(EntityRepository::class);
        $repoMock->method('createQueryBuilder')->willReturn($qb);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repoMock);

        $repository = new ServiceOrderRepository($em);
        $next = $repository->nextOrderNumber();

        $this->assertStringEndsWith('-0011', $next);
    }

    public function testSaveAndDeleteCallEntityManager(): void
    {
        $order = $this->createMock(ServiceOrder::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($order);
        $em->expects($this->once())->method('flush');

        $repository = new ServiceOrderRepository($em);
        $repository->save($order);

        $em2 = $this->createMock(EntityManagerInterface::class);
        $em2->expects($this->once())->method('remove')->with($order);
        $em2->expects($this->once())->method('flush');

        $repository2 = new ServiceOrderRepository($em2);
        $repository2->delete($order);
    }

    public function testGetAverageExecutionTimeByServiceUsesConnectionFetch(): void
    {
        $conn = $this->createMock(Connection::class);
        $expected = [['name' => 'S1', 'avgTime' => 100]];
        $conn->expects($this->once())->method('fetchAllAssociative')->willReturn($expected);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($this->createMock(EntityRepository::class));
        $em->method('getConnection')->willReturn($conn);

        $repository = new ServiceOrderRepository($em);
        $this->assertSame($expected, $repository->getAverageExecutionTimeByService());
    }
}
