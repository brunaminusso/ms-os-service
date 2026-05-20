<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\VehiclePlate;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use DateTimeImmutable;

#[ORM\Entity]
#[ORM\Table(name: 'vehicles')]
class Vehicle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 7, unique: true)]
    private string $plate;

    #[ORM\Column(type: 'string', length: 100)]
    private string $brand;

    #[ORM\Column(type: 'string', length: 100)]
    private string $model;

    #[ORM\Column(type: 'integer')]
    private int $year;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $color = null;

    #[ORM\ManyToOne(targetEntity: Client::class, inversedBy: 'vehicles')]
    #[ORM\JoinColumn(nullable: false)]
    private Client $client;

    #[ORM\OneToMany(targetEntity: ServiceOrder::class, mappedBy: 'vehicle')]
    private Collection $serviceOrders;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    public function __construct(VehiclePlate $plate, string $brand, string $model, int $year, Client $client)
    {
        $this->plate = $plate->getValue();
        $this->brand = $brand;
        $this->model = $model;
        $this->year = $year;
        $this->client = $client;
        $this->serviceOrders = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlate(): VehiclePlate
    {
        return new VehiclePlate($this->plate);
    }

    public function setPlate(VehiclePlate $plate): self
    {
        $this->plate = $plate->getValue();
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getBrand(): string
    {
        return $this->brand;
    }

    public function setBrand(string $brand): self
    {
        $this->brand = $brand;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function setModel(string $model): self
    {
        $this->model = $model;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function setYear(int $year): self
    {
        $this->year = $year;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): self
    {
        $this->color = $color;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function setClient(Client $client): self
    {
        $this->client = $client;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getServiceOrders(): Collection
    {
        return $this->serviceOrders;
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
