<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCase\ServiceOrder;

use App\Application\UseCase\ServiceOrder\ApproveQuotationUseCase;
use App\Domain\Entity\Client;
use App\Domain\Entity\ServiceOrder;
use App\Domain\Entity\Vehicle;
use App\Domain\Enum\OrderStatus;
use App\Domain\Repository\ServiceOrderRepositoryInterface;
use App\Domain\Service\NotificationService;
use App\Domain\Service\PartReservationService;
use App\Domain\ValueObject\VehiclePlate;
use PHPUnit\Framework\TestCase;

final class ApproveQuotationUseCaseTest extends TestCase
{
    public function testExecuteApprovesQuotationAndNotifies(): void
    {
        $repository = $this->createMock(ServiceOrderRepositoryInterface::class);
        $notificationService = $this->createMock(NotificationService::class);
        $reservationService = $this->createMock(PartReservationService::class);

        $repository->expects($this->once())->method('save');
        $reservationService->expects($this->once())->method('confirmReservations');
        $notificationService->expects($this->once())->method('notifyQuotationApproved');
        $notificationService->expects($this->once())
            ->method('notifyStatusChanged')
            ->with($this->isInstanceOf(ServiceOrder::class), 'awaiting_approval');

        $client = new Client('Cli');
        $vehicle = new Vehicle(new VehiclePlate('ABC-1234'), 'Brand', 'Model', 2020, $client);
        $order = new ServiceOrder('SO-1', $client, $vehicle);
        $order->changeStatus(OrderStatus::IN_DIAGNOSTIC);
        $order->sendQuotation();

        $useCase = new ApproveQuotationUseCase(
            $repository,
            $notificationService,
            $reservationService
        );

        $result = $useCase->execute($order);

        $this->assertSame($order, $result);
        $this->assertTrue($order->isQuotationApproved());
        $this->assertSame(OrderStatus::IN_EXECUTION, $order->getStatus());
    }
}
