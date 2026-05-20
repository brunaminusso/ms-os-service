<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;

#[ORM\Entity]
#[ORM\Table(name: 'part_reservations')]
class PartReservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ServiceOrder::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ServiceOrder $serviceOrder;

    #[ORM\ManyToOne(targetEntity: Part::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Part $part;

    #[ORM\Column(type: 'integer')]
    private int $quantity;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'reserved';

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $reservedAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $expiresAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $confirmedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $cancelledAt = null;

    public function __construct(ServiceOrder $serviceOrder, Part $part, int $quantity, int $hoursToExpire = 48)
    {
        $this->serviceOrder = $serviceOrder;
        $this->part = $part;
        $this->quantity = $quantity;
        $this->reservedAt = new DateTimeImmutable();
        $this->expiresAt = $this->reservedAt->modify("+{$hoursToExpire} hours");
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getServiceOrder(): ServiceOrder
    {
        return $this->serviceOrder;
    }

    public function getPart(): Part
    {
        return $this->part;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getReservedAt(): DateTimeImmutable
    {
        return $this->reservedAt;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getConfirmedAt(): ?DateTimeImmutable
    {
        return $this->confirmedAt;
    }

    public function getCancelledAt(): ?DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function isExpired(): bool
    {
        return $this->status === 'reserved' && new DateTimeImmutable() > $this->expiresAt;
    }

    public function confirm(): self
    {
        if ($this->status !== 'reserved') {
            throw new \DomainException("Apenas reservas pendentes podem ser confirmadas");
        }

        if ($this->isExpired()) {
            throw new \DomainException("Não é possível confirmar uma reserva expirada");
        }

        $this->status = 'confirmed';
        $this->confirmedAt = new DateTimeImmutable();
        return $this;
    }

    public function cancel(): self
    {
        if ($this->status === 'confirmed') {
            throw new \DomainException("Não é possível cancelar uma reserva já confirmada");
        }

        $this->status = 'cancelled';
        $this->cancelledAt = new DateTimeImmutable();
        return $this;
    }

    public function expire(): self
    {
        if ($this->status !== 'reserved') {
            throw new \DomainException("Apenas reservas pendentes podem expirar");
        }

        $this->status = 'expired';
        $this->cancelledAt = new DateTimeImmutable();
        return $this;
    }
}
