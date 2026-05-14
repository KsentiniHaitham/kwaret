<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
class Notification
{
    // Admin-side types
    const TYPE_NEW_ORDER    = 'new_order';
    const TYPE_OUT_OF_STOCK = 'out_of_stock';
    const TYPE_NEW_RECHARGE = 'new_recharge';

    // User-side types
    const TYPE_RECHARGE_APPROVED = 'recharge_approved';
    const TYPE_RECHARGE_REJECTED = 'recharge_rejected';
    const TYPE_ORDER_STATUS      = 'order_status';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['notification:read'])]
    private ?int $id = null;

    /** null = admin notification, set = user notification */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 50)]
    #[Groups(['notification:read'])]
    private string $type = '';

    #[ORM\Column(type: 'text')]
    #[Groups(['notification:read'])]
    private string $message = '';

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['notification:read'])]
    private ?array $data = null;

    #[ORM\Column]
    #[Groups(['notification:read'])]
    private bool $isRead = false;

    #[ORM\Column]
    #[Groups(['notification:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }

    public function getMessage(): string { return $this->message; }
    public function setMessage(string $message): static { $this->message = $message; return $this; }

    public function getData(): ?array { return $this->data; }
    public function setData(?array $data): static { $this->data = $data; return $this; }

    public function isRead(): bool { return $this->isRead; }
    public function setIsRead(bool $isRead): static { $this->isRead = $isRead; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
