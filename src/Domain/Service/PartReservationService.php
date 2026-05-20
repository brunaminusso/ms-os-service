<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\PartReservation;
use App\Domain\Entity\ServiceOrder;
use App\Domain\Repository\PartReservationRepositoryInterface;
use App\Domain\Enum\OrderStatus;

class PartReservationService
{
    public function __construct(
        private PartReservationRepositoryInterface $reservationRepository
    ) {
    }

    public function createReservationsForOrder(ServiceOrder $order): array
    {
        if (
            $order->getStatus() !== OrderStatus::IN_DIAGNOSTIC &&
            $order->getStatus() !== OrderStatus::AWAITING_APPROVAL
        ) {
            throw new \DomainException('Apenas ordens em diagnóstico podem ter peças reservadas');
        }

        $reservations = [];

        foreach ($order->getParts() as $orderPart) {
            $part = $orderPart->getPart();
            $quantity = $orderPart->getQuantity();

            if ($part->getStockQuantity() < $quantity) {
                throw new \DomainException(
                    sprintf(
                        'Estoque insuficiente para a peça "%s". Disponível: %d, Necessário: %d',
                        $part->getName(),
                        $part->getStockQuantity(),
                        $quantity
                    )
                );
            }

            $reservation = new PartReservation($order, $part, $quantity, 48);
            $this->reservationRepository->save($reservation);

            $reservations[] = $reservation;
        }

        return $reservations;
    }

    public function confirmReservations(ServiceOrder $order): void
    {
        $reservations = $this->reservationRepository->findByServiceOrder($order);

        foreach ($reservations as $reservation) {
            if ($reservation->getStatus() === 'reserved') {
                $reservation->confirm();

                $reservation->getPart()->removeStock($reservation->getQuantity());

                $this->reservationRepository->save($reservation);
            }
        }
    }

    public function cancelReservations(ServiceOrder $order): void
    {
        $reservations = $this->reservationRepository->findByServiceOrder($order);

        foreach ($reservations as $reservation) {
            if ($reservation->getStatus() === 'reserved') {
                $reservation->cancel();
                $this->reservationRepository->save($reservation);
            }
        }
    }

    public function expireOldReservations(): int
    {
        $expiredReservations = $this->reservationRepository->findExpired();
        $count = 0;

        foreach ($expiredReservations as $reservation) {
            $reservation->expire();
            $this->reservationRepository->save($reservation);
            $count++;
        }

        return $count;
    }

    public function hasActiveReservations(ServiceOrder $order): bool
    {
        $reservations = $this->reservationRepository->findByServiceOrder($order);

        foreach ($reservations as $reservation) {
            if ($reservation->getStatus() === 'reserved') {
                return true;
            }
        }

        return false;
    }
}
