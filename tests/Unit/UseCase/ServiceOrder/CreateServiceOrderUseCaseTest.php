<?php

declare(strict_types=1);

namespace Tests\Unit\UseCase\ServiceOrder;

use App\Application\UseCase\ServiceOrder\CreateServiceOrderUseCase;
use App\Application\Saga\ServiceOrderSagaOrchestrator;
use App\Domain\Entity\Client;
use App\Domain\Entity\ServiceOrder;
use App\Domain\Entity\Vehicle;
use App\Domain\Repository\ServiceOrderRepositoryInterface;
use App\Domain\Service\NotificationService;
use App\Domain\ValueObject\VehiclePlate;
use PHPUnit\Framework\TestCase;

final class CreateServiceOrderUseCaseTest extends TestCase
{
    public function testExecuteCallsRepositoryNotificationAndOrchestrator(): void
    {
        $repo = $this->createMock(ServiceOrderRepositoryInterface::class);
        $notify = $this->createMock(NotificationService::class);
        $orchestrator = $this->createMock(ServiceOrderSagaOrchestrator::class);

        $repo->expects($this->once())->method('save');
        $notify->expects($this->once())->method('notifyServiceOrderCreated');
        $orchestrator->expects($this->once())->method('requestQuotation');

        $useCase = new CreateServiceOrderUseCase($repo, $notify, $orchestrator);

        $client = new Client('Cliente Test');
        $vehicle = new Vehicle(new VehiclePlate('ZZZ9999'), 'Brand', 'Model', 2022, $client);
        $order = new ServiceOrder('ORD-CT-1', $client, $vehicle);

        $result = $useCase->execute($order);

        $this->assertSame($order, $result);
    }

    public function testExecuteWhenOrchestratorThrowsCallsCompensate(): void
    {
        $repo = $this->createMock(ServiceOrderRepositoryInterface::class);
        $notify = $this->createMock(NotificationService::class);
        $orchestrator = $this->createMock(ServiceOrderSagaOrchestrator::class);

        $repo->expects($this->once())->method('save');
        $notify->expects($this->once())->method('notifyServiceOrderCreated');
        $orchestrator->expects($this->once())->method('requestQuotation')
            ->willThrowException(new \RuntimeException('boom'));
        $orchestrator->expects($this->once())->method('compensateQuotationFailure');

        $useCase = new CreateServiceOrderUseCase($repo, $notify, $orchestrator);

        $client = new Client('Cliente Test');
        $vehicle = new Vehicle(new VehiclePlate('ZZZ9998'), 'Brand', 'Model', 2022, $client);
        $order = new ServiceOrder('ORD-CT-2', $client, $vehicle);

        $result = $useCase->execute($order);

        $this->assertSame($order, $result);
    }
}
