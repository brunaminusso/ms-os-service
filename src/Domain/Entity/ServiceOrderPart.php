<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\Money;
use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;

#[ORM\Entity]
#[ORM\Table(name: 'service_order_parts')]
class ServiceOrderPart
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ServiceOrder::class, inversedBy: 'parts')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ServiceOrder $serviceOrder;

    #[ORM\ManyToOne(targetEntity: Part::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Part $part;

    #[ORM\Column(type: 'integer')]
    private int $quantity;

    #[ORM\Column(type: 'integer')]
    private int $unitPriceInCents;

    #[ORM\Column(type: 'integer')]
    private int $subtotalInCents;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    public function __construct(ServiceOrder $serviceOrder, Part $part, int $quantity)
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException("Quantidade deve ser maior que zero");
        }

        if ($part->getStockQuantity() < $quantity) {
            throw new \DomainException("Estoque insuficiente para a peça: {$part->getName()}");
        }

        $this->serviceOrder = $serviceOrder;
        $this->part = $part;
        $this->quantity = $quantity;
        $this->unitPriceInCents = $part->getPrice()->getAmountInCents();
        $this->subtotalInCents = $this->unitPriceInCents * $quantity;
        $this->createdAt = new DateTimeImmutable();
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

    public function setQuantity(int $quantity): self
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException("Quantidade deve ser maior que zero");
        }

        $this->quantity = $quantity;
        $this->subtotalInCents = $this->unitPriceInCents * $quantity;
        return $this;
    }

    public function getUnitPrice(): Money
    {
        return Money::fromCents($this->unitPriceInCents);
    }

    public function getSubtotal(): Money
    {
        return Money::fromCents($this->subtotalInCents);
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
