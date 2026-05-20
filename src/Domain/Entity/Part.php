<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\Money;
use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;

#[ORM\Entity]
#[ORM\Table(name: 'parts')]
class Part
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 100, unique: true, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'integer')]
    private int $priceInCents;

    #[ORM\Column(type: 'integer')]
    private int $stockQuantity = 0;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $minimumStock = null;

    #[ORM\Column(type: 'boolean')]
    private bool $active = true;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    public function __construct(string $name, Money $price)
    {
        $this->name = $name;
        $this->priceInCents = $price->getAmountInCents();
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): self
    {
        $this->code = $code;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getPrice(): Money
    {
        return Money::fromCents($this->priceInCents);
    }

    public function setPrice(Money $price): self
    {
        $this->priceInCents = $price->getAmountInCents();
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getStockQuantity(): int
    {
        return $this->stockQuantity;
    }

    public function addStock(int $quantity): self
    {
        if ($quantity < 0) {
            throw new \InvalidArgumentException("Quantidade não pode ser negativa");
        }
        $this->stockQuantity += $quantity;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function removeStock(int $quantity): self
    {
        if ($quantity < 0) {
            throw new \InvalidArgumentException("Quantidade não pode ser negativa");
        }
        if ($this->stockQuantity < $quantity) {
            throw new \DomainException("Estoque insuficiente");
        }
        $this->stockQuantity -= $quantity;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getMinimumStock(): ?int
    {
        return $this->minimumStock;
    }

    public function setMinimumStock(?int $minimumStock): self
    {
        $this->minimumStock = $minimumStock;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function isLowStock(): bool
    {
        return $this->minimumStock !== null && $this->stockQuantity <= $this->minimumStock;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
