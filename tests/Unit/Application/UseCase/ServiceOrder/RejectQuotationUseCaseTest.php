<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCase\ServiceOrder;

use App\Application\UseCase\ServiceOrder\RejectQuotationUseCase;
use App\Domain\Entity\Client;
use App\Domain\Entity\ServiceOrder;
use App\Domain\Entity\Vehicle;
use App\Domain\Enum\OrderStatus;
use App\Domain\Repository\ServiceOrderRepositoryInterface;
use App\Domain\Service\NotificationService;
use App\Domain\Service\PartReservationService;
use App\Domain\ValueObject\VehiclePlate;
use PHPUnit\Framework\TestCase;

final class RejectQuotationUseCaseTest extends TestCase
{
    public function testExecuteRejectsQuotationAndCancelsOrder(): void
    {
        $repository = $this->createMock(ServiceOrderRepositoryInterface::class);
        $notificationService = $this->createMock(NotificationService::class);
        $reservationService = $this->createMock(PartReservationService::class);

        $repository->expects($this->once())->method('save');
        $reservationService->expects($this->once())->method('cancelReservations');
        $notificationService->expects($this->once())->method('notifyQuotationRejected');

        $client = new Client('Cli');
        $vehicle = new Vehicle(new VehiclePlate('ABC-1234'), 'Brand', 'Model', 2020, $client);
        $order = new ServiceOrder('SO-2', $client, $vehicle);
        $order->changeStatus(OrderStatus::IN_DIAGNOSTIC);
        $order->sendQuotation();

        $useCase = new RejectQuotationUseCase(
            $repository,
            $notificationService,
            $reservationService
        );

        $reason = 'nao aprovado';
        $result = $useCase->execute($order, $reason);

        $this->assertSame($order, $result);
        $this->assertTrue($order->isQuotationRejected());
        $this->assertTrue($order->isCancelled());
        $this->assertSame($reason, $order->getRejectionReason());
        $this->assertSame('Orçamento rejeitado pelo cliente: ' . $reason, $order->getCancellationReason());
    }
}
