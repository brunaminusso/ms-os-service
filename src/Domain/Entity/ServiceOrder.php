<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Enum\OrderStatus;
use App\Domain\ValueObject\Money;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use DateTimeImmutable;

#[ORM\Entity]
#[ORM\Table(name: 'service_orders')]
class ServiceOrder
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    private string $orderNumber;

    #[ORM\ManyToOne(targetEntity: Client::class, inversedBy: 'serviceOrders')]
    #[ORM\JoinColumn(nullable: false)]
    private Client $client;

    #[ORM\ManyToOne(targetEntity: Vehicle::class, inversedBy: 'serviceOrders')]
    #[ORM\JoinColumn(nullable: false)]
    private Vehicle $vehicle;

    #[ORM\Column(type: 'string', length: 50, enumType: OrderStatus::class)]
    private OrderStatus $status;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $diagnosticNotes = null;

    /**
     * @var Collection<int, ServiceOrderService>
     */
    #[ORM\OneToMany(
        targetEntity: ServiceOrderService::class,
        mappedBy: 'serviceOrder',
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    private Collection $services;

    /**
     * @var Collection<int, ServiceOrderPart>
     */
    #[ORM\OneToMany(
        targetEntity: ServiceOrderPart::class,
        mappedBy: 'serviceOrder',
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    private Collection $parts;

    #[ORM\OneToMany(
        targetEntity: ServiceOrderStatusHistory::class,
        mappedBy: 'serviceOrder',
        cascade: ['persist'],
        orphanRemoval: true,
    )]
    #[ORM\OrderBy(['changedAt' => 'ASC'])]
    /**
     * @var Collection<int, ServiceOrderStatusHistory>
     */
    private Collection $statusHistory;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $quotationTotalInCents = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $quotationApprovedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $quotationSentAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $quotationRejectedAt = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $rejectionReason = null;

    #[ORM\Column(type: 'boolean')]
    private bool $cancelled = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $cancelledAt = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $cancellationReason = null;

    #[ORM\Column(type: 'integer')]
    private int $quotationRemindersSent = 0;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $lastReminderSentAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $startedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $finishedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $deliveredAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    public function __construct(string $orderNumber, Client $client, Vehicle $vehicle, ?string $description = null)
    {
        $this->orderNumber = $orderNumber;
        $this->client = $client;
        $this->vehicle = $vehicle;
        $this->description = $description;
        $this->status = OrderStatus::RECEIVED;
        $this->services = new ArrayCollection();
        $this->parts = new ArrayCollection();
        $this->statusHistory = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();

        $this->addStatusHistory(OrderStatus::RECEIVED);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderNumber(): string
    {
        return $this->orderNumber;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getVehicle(): Vehicle
    {
        return $this->vehicle;
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function changeStatus(OrderStatus $newStatus): self
    {
        if (!$this->status->canTransitionTo($newStatus)) {
            throw new \DomainException(
                sprintf(
                    "Transição de status inválida: %s -> %s",
                    $this->status->getLabel(),
                    $newStatus->getLabel()
                )
            );
        }

        $this->status = $newStatus;
        $this->addStatusHistory($newStatus);

        match ($newStatus) {
            OrderStatus::IN_EXECUTION => $this->startedAt = new DateTimeImmutable(),
            OrderStatus::FINISHED => $this->finishedAt = new DateTimeImmutable(),
            OrderStatus::DELIVERED => $this->deliveredAt = new DateTimeImmutable(),
            default => null
        };

        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    private function addStatusHistory(OrderStatus $status): void
    {
        $history = new ServiceOrderStatusHistory($this, $status);
        $this->statusHistory->add($history);
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

    public function getDiagnosticNotes(): ?string
    {
        return $this->diagnosticNotes;
    }

    public function setDiagnosticNotes(?string $notes): self
    {
        $this->diagnosticNotes = $notes;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    /**
     * @return Collection<int, ServiceOrderService>
     */
    public function getServices(): Collection
    {
        return $this->services;
    }

    public function addService(Service $service, int $quantity = 1): self
    {
        if (!in_array($this->status, [OrderStatus::RECEIVED, OrderStatus::IN_DIAGNOSTIC])) {
            throw new \DomainException(
                'Serviços só podem ser adicionados nos status: Recebida ou Em diagnóstico'
            );
        }

        if ($this->cancelled) {
            throw new \DomainException('Não é possível adicionar serviços em uma ordem cancelada');
        }

        $orderService = new ServiceOrderService($this, $service, $quantity);
        $this->services->add($orderService);
        $this->recalculateQuotation();
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function removeService(ServiceOrderService $orderService): self
    {
        if (!in_array($this->status, [OrderStatus::RECEIVED, OrderStatus::IN_DIAGNOSTIC])) {
            throw new \DomainException(
                'Serviços só podem ser removidos nos status: Recebida ou Em diagnóstico'
            );
        }

        if ($this->cancelled) {
            throw new \DomainException('Não é possível remover serviços de uma ordem cancelada');
        }

        $this->services->removeElement($orderService);
        $this->recalculateQuotation();
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    /**
     * @return Collection<int, ServiceOrderPart>
     */
    public function getParts(): Collection
    {
        return $this->parts;
    }

    public function addPart(Part $part, int $quantity): self
    {
        if (!in_array($this->status, [OrderStatus::RECEIVED, OrderStatus::IN_DIAGNOSTIC])) {
            throw new \DomainException(
                'Peças só podem ser adicionadas nos status: Recebida ou Em diagnóstico'
            );
        }

        if ($this->cancelled) {
            throw new \DomainException('Não é possível adicionar peças em uma ordem cancelada');
        }

        $orderPart = new ServiceOrderPart($this, $part, $quantity);
        $this->parts->add($orderPart);
        $this->recalculateQuotation();
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function removePart(ServiceOrderPart $orderPart): self
    {
        if (!in_array($this->status, [OrderStatus::RECEIVED, OrderStatus::IN_DIAGNOSTIC])) {
            throw new \DomainException(
                'Peças só podem ser removidas nos status: Recebida ou Em diagnóstico'
            );
        }

        if ($this->cancelled) {
            throw new \DomainException('Não é possível remover peças de uma ordem cancelada');
        }

        $this->parts->removeElement($orderPart);
        $this->recalculateQuotation();
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    private function recalculateQuotation(): void
    {
        $total = new Money(0);

        foreach ($this->services as $serviceItem) {
            $total = $total->add($serviceItem->getSubtotal());
        }

        foreach ($this->parts as $partItem) {
            $total = $total->add($partItem->getSubtotal());
        }

        $this->quotationTotalInCents = $total->getAmountInCents();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getQuotationTotal(): ?Money
    {
        return $this->quotationTotalInCents !== null
            ? Money::fromCents($this->quotationTotalInCents)
            : null;
    }

    public function approveQuotation(): self
    {
        if ($this->status !== OrderStatus::AWAITING_APPROVAL) {
            throw new \DomainException("Ordem de serviço não está aguardando aprovação");
        }

        if ($this->cancelled) {
            throw new \DomainException("Ordem de serviço cancelada não pode ser aprovada");
        }

        $this->quotationApprovedAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function rejectQuotation(string $reason): self
    {
        if ($this->status !== OrderStatus::AWAITING_APPROVAL) {
            throw new \DomainException("Ordem de serviço não está aguardando aprovação");
        }

        $this->quotationRejectedAt = new DateTimeImmutable();
        $this->rejectionReason = $reason;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function cancel(string $reason): self
    {
        if ($this->status === OrderStatus::DELIVERED) {
            throw new \DomainException("Ordem de serviço entregue não pode ser cancelada");
        }

        if ($this->status === OrderStatus::FINISHED) {
            throw new \DomainException("Ordem de serviço finalizada não pode ser cancelada");
        }

        $this->cancelled = true;
        $this->cancelledAt = new DateTimeImmutable();
        $this->cancellationReason = $reason;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function isCancelled(): bool
    {
        return $this->cancelled;
    }

    public function sendQuotation(): self
    {
        if ($this->status !== OrderStatus::AWAITING_APPROVAL) {
            $this->changeStatus(OrderStatus::AWAITING_APPROVAL);
        }

        $this->quotationSentAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function sendReminder(): self
    {
        if ($this->status !== OrderStatus::AWAITING_APPROVAL) {
            throw new \DomainException("Apenas orçamentos aguardando aprovação podem receber lembretes");
        }

        $this->quotationRemindersSent++;
        $this->lastReminderSentAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function shouldSendReminder(): bool
    {
        if ($this->status !== OrderStatus::AWAITING_APPROVAL || !$this->quotationSentAt) {
            return false;
        }

        $now = new DateTimeImmutable();
        $hoursSinceSent = ($now->getTimestamp() - $this->quotationSentAt->getTimestamp()) / 3600;

        if ($hoursSinceSent >= 24 && $this->quotationRemindersSent === 0) {
            return true;
        }

        return false;
    }

    public function shouldAutoCancelForTimeout(): bool
    {
        if ($this->status !== OrderStatus::AWAITING_APPROVAL || !$this->quotationSentAt) {
            return false;
        }

        $now = new DateTimeImmutable();
        $hoursSinceSent = ($now->getTimestamp() - $this->quotationSentAt->getTimestamp()) / 3600;

        return $hoursSinceSent >= 48;
    }

    public function isQuotationApproved(): bool
    {
        return $this->quotationApprovedAt !== null;
    }

    public function isQuotationRejected(): bool
    {
        return $this->quotationRejectedAt !== null;
    }

    public function getQuotationApprovedAt(): ?DateTimeImmutable
    {
        return $this->quotationApprovedAt;
    }

    public function getQuotationRejectedAt(): ?DateTimeImmutable
    {
        return $this->quotationRejectedAt;
    }

    public function getQuotationSentAt(): ?DateTimeImmutable
    {
        return $this->quotationSentAt;
    }

    /**
     * @return Collection<int, ServiceOrderStatusHistory>
     */
    public function getStatusHistory(): Collection
    {
        return $this->statusHistory;
    }

    public function getAverageExecutionTime(): ?int
    {
        if (!$this->finishedAt || !$this->startedAt) {
            return null;
        }

        return $this->finishedAt->getTimestamp() - $this->startedAt->getTimestamp();
    }

    public function getStartedAt(): ?DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getFinishedAt(): ?DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function getDeliveredAt(): ?DateTimeImmutable
    {
        return $this->deliveredAt;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function getCancelledAt(): ?DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function getCancellationReason(): ?string
    {
        return $this->cancellationReason;
    }

    public function getLastReminderSentAt(): ?DateTimeImmutable
    {
        return $this->lastReminderSentAt;
    }
}
