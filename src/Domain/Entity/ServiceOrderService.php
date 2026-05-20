<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\Money;
use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;

#[ORM\Entity]
#[ORM\Table(name: 'service_order_services')]
class ServiceOrderService
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ServiceOrder::class, inversedBy: 'services')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ServiceOrder $serviceOrder;

    #[ORM\ManyToOne(targetEntity: Service::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Service $service;

    #[ORM\Column(type: 'integer')]
    private int $quantity;

    #[ORM\Column(type: 'integer')]
    private int $unitPriceInCents;

    #[ORM\Column(type: 'integer')]
    private int $subtotalInCents;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    public function __construct(ServiceOrder $serviceOrder, Service $service, int $quantity = 1)
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException("Quantidade deve ser maior que zero");
        }

        $this->serviceOrder = $serviceOrder;
        $this->service = $service;
        $this->quantity = $quantity;
        $this->unitPriceInCents = $service->getPrice()->getAmountInCents();
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

    public function getService(): Service
    {
        return $this->service;
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
