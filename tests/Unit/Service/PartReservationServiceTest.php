<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Domain\Entity\Client;
use App\Domain\Entity\Part;
use App\Domain\Entity\PartReservation;
use App\Domain\Entity\ServiceOrder;
use App\Domain\Entity\Vehicle;
use App\Domain\Enum\OrderStatus;
use App\Domain\Repository\PartReservationRepositoryInterface;
use App\Domain\Service\PartReservationService;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\VehiclePlate;
use PHPUnit\Framework\TestCase;

final class PartReservationServiceTest extends TestCase
{
    public function testCreateReservationsForOrderReturnsReservations(): void
    {
        $part = $this->createPart('Filtro', 10.0, 5);
        $order = $this->createOrder(OrderStatus::IN_DIAGNOSTIC, $part, 2);

        $reservationRepository = $this->createMock(PartReservationRepositoryInterface::class);
        $reservationRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(PartReservation::class));

        $service = new PartReservationService($reservationRepository);
        $reservations = $service->createReservationsForOrder($order);

        $this->assertCount(1, $reservations);
        $this->assertSame(2, $reservations[0]->getQuantity());
        $this->assertSame('reserved', $reservations[0]->getStatus());
    }

    public function testCreateReservationsForOrderThrowsWhenStatusIsInvalid(): void
    {
        $service = new PartReservationService($this->createMock(PartReservationRepositoryInterface::class));
        $order = $this->createOrder(OrderStatus::RECEIVED, $this->createPart('Filtro', 10.0, 5), 1);

        $this->expectException(\DomainException::class);
        $service->createReservationsForOrder($order);
    }

    public function testCreateReservationsForOrderThrowsWhenStockIsInsufficient(): void
    {
        $part = $this->createPart('Filtro', 10.0, 2);
        $order = $this->createOrder(OrderStatus::IN_DIAGNOSTIC, $part, 2);

        // make stock insufficient after order creation
        $part->removeStock(1);

        $service = new PartReservationService($this->createMock(PartReservationRepositoryInterface::class));

        $this->expectException(\DomainException::class);
        $service->createReservationsForOrder($order);
    }

    public function testConfirmReservationsConfirmsReservedItemsAndReducesStock(): void
    {
        $part = $this->createPart('Filtro', 10.0, 5);
        $order = $this->createOrder(OrderStatus::IN_DIAGNOSTIC, $part, 2);
        $reservation = new PartReservation($order, $part, 2, 48);

        $reservationRepository = $this->createMock(PartReservationRepositoryInterface::class);
        $reservationRepository->expects($this->once())
            ->method('findByServiceOrder')
            ->with($order)
            ->willReturn([$reservation]);
        $reservationRepository->expects($this->once())->method('save')->with($reservation);

        $service = new PartReservationService($reservationRepository);
        $service->confirmReservations($order);

        $this->assertSame('confirmed', $reservation->getStatus());
        $this->assertSame(3, $part->getStockQuantity());
    }

    public function testCancelReservationsCancelsReservedItems(): void
    {
        $part = $this->createPart('Filtro', 10.0, 5);
        $order = $this->createOrder(OrderStatus::IN_DIAGNOSTIC, $part, 2);
        $reservation = new PartReservation($order, $part, 2, 48);

        $reservationRepository = $this->createMock(PartReservationRepositoryInterface::class);
        $reservationRepository->expects($this->once())
            ->method('findByServiceOrder')
            ->with($order)
            ->willReturn([$reservation]);
        $reservationRepository->expects($this->once())->method('save')->with($reservation);

        $service = new PartReservationService($reservationRepository);
        $service->cancelReservations($order);

        $this->assertSame('cancelled', $reservation->getStatus());
    }

    public function testExpireOldReservationsExpiresThem(): void
    {
        $part = $this->createPart('Filtro', 10.0, 5);
        $order = $this->createOrder(OrderStatus::IN_DIAGNOSTIC, $part, 2);
        $reservation = new PartReservation($order, $part, 2, 48);

        $reservationRepository = $this->createMock(PartReservationRepositoryInterface::class);
        $reservationRepository->expects($this->once())->method('findExpired')->willReturn([$reservation]);
        $reservationRepository->expects($this->once())->method('save')->with($reservation);

        $service = new PartReservationService($reservationRepository);
        $count = $service->expireOldReservations();

        $this->assertSame(1, $count);
        $this->assertSame('expired', $reservation->getStatus());
    }

    public function testHasActiveReservationsReturnsTrueWhenPendingReservationExists(): void
    {
        $part = $this->createPart('Filtro', 10.0, 5);
        $order = $this->createOrder(OrderStatus::IN_DIAGNOSTIC, $part, 2);
        $reservation = new PartReservation($order, $part, 2, 48);

        $reservationRepository = $this->createMock(PartReservationRepositoryInterface::class);
        $reservationRepository->method('findByServiceOrder')->with($order)->willReturn([$reservation]);

        $service = new PartReservationService($reservationRepository);

        $this->assertTrue($service->hasActiveReservations($order));
    }

    public function testHasActiveReservationsReturnsFalseWhenNoPendingReservationExists(): void
    {
        $order = $this->createOrder(OrderStatus::IN_DIAGNOSTIC, $this->createPart('Filtro', 10.0, 5), 2);
        $reservation = new PartReservation($order, $this->createPart('Filtro', 10.0, 5), 2, 48);
        $reservation->confirm();

        $reservationRepository = $this->createMock(PartReservationRepositoryInterface::class);
        $reservationRepository->method('findByServiceOrder')->with($order)->willReturn([$reservation]);

        $service = new PartReservationService($reservationRepository);

        $this->assertFalse($service->hasActiveReservations($order));
    }

    private function createPart(string $name, float $price, int $stockQuantity): Part
    {
        $part = new Part($name, new Money($price));
        $part->addStock($stockQuantity);
        return $part;
    }

    private function createOrder(OrderStatus $status, Part $part, int $quantity): ServiceOrder
    {
        $client = new Client('Cliente');
        $vehicle = new Vehicle(new VehiclePlate('ABC1234'), 'Brand', 'Model', 2020, $client);
        $order = new ServiceOrder('ORD-1', $client, $vehicle);

        if ($status === OrderStatus::IN_DIAGNOSTIC) {
            $order->changeStatus(OrderStatus::IN_DIAGNOSTIC);
        }

        if ($status === OrderStatus::AWAITING_APPROVAL) {
            $order->changeStatus(OrderStatus::IN_DIAGNOSTIC);
            $order->sendQuotation();
        }

        $order->addPart($part, $quantity);

        return $order;
    }
}