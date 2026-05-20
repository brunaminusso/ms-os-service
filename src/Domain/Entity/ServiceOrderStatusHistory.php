<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Enum\OrderStatus;
use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;

#[ORM\Entity]
#[ORM\Table(name: 'service_order_status_history')]
class ServiceOrderStatusHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ServiceOrder::class, inversedBy: 'statusHistory')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ServiceOrder $serviceOrder;

    #[ORM\Column(type: 'string', length: 50, enumType: OrderStatus::class)]
    private OrderStatus $status;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $changedAt;

    public function __construct(ServiceOrder $serviceOrder, OrderStatus $status, ?string $notes = null)
    {
        $this->serviceOrder = $serviceOrder;
        $this->status = $status;
        $this->notes = $notes;
        $this->changedAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getServiceOrder(): ServiceOrder
    {
        return $this->serviceOrder;
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    public function getChangedAt(): DateTimeImmutable
    {
        return $this->changedAt;
    }
}
