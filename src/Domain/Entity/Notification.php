<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;

#[ORM\Entity]
#[ORM\Table(name: 'notifications')]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Client::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Client $client;

    #[ORM\ManyToOne(targetEntity: ServiceOrder::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?ServiceOrder $serviceOrder = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $type;

    #[ORM\Column(type: 'string', length: 100)]
    private string $event;

    #[ORM\Column(type: 'string', length: 255)]
    private string $subject;

    #[ORM\Column(type: 'text')]
    private string $message;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'pending';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $sentAt = null;

    public function __construct(
        Client $client,
        string $type,
        string $event,
        string $subject,
        string $message,
        ?ServiceOrder $serviceOrder = null
    ) {
        $this->client = $client;
        $this->type = $type;
        $this->event = $event;
        $this->subject = $subject;
        $this->message = $message;
        $this->serviceOrder = $serviceOrder;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getServiceOrder(): ?ServiceOrder
    {
        return $this->serviceOrder;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getEvent(): string
    {
        return $this->event;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function markAsSent(): self
    {
        $this->status = 'sent';
        $this->sentAt = new DateTimeImmutable();
        return $this;
    }

    public function markAsFailed(string $errorMessage): self
    {
        $this->status = 'failed';
        $this->errorMessage = $errorMessage;
        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getSentAt(): ?DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }
}
