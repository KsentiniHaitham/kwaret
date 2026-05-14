<?php

namespace App\Entity;

use App\Repository\WalletRechargeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: WalletRechargeRepository::class)]
class WalletRecharge
{
    const STATUS_PENDING  = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['recharge:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['recharge:read'])]
    private ?User $user = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Groups(['recharge:read'])]
    private string $amount;

    #[ORM\Column(length: 50)]
    #[Groups(['recharge:read'])]
    private string $method;

    #[ORM\Column(length: 20)]
    #[Groups(['recharge:read'])]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['recharge:read'])]
    private ?string $proofPath = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['recharge:read'])]
    private ?string $senderInfo = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['recharge:read'])]
    private ?string $adminNote = null;

    #[ORM\Column]
    #[Groups(['recharge:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    #[Groups(['recharge:read'])]
    private ?\DateTimeImmutable $processedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getAmount(): string { return $this->amount; }
    public function setAmount(string $amount): static { $this->amount = $amount; return $this; }

    public function getMethod(): string { return $this->method; }
    public function setMethod(string $method): static { $this->method = $method; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getProofPath(): ?string { return $this->proofPath; }
    public function setProofPath(?string $proofPath): static { $this->proofPath = $proofPath; return $this; }

    public function getSenderInfo(): ?string { return $this->senderInfo; }
    public function setSenderInfo(?string $senderInfo): static { $this->senderInfo = $senderInfo; return $this; }

    public function getAdminNote(): ?string { return $this->adminNote; }
    public function setAdminNote(?string $adminNote): static { $this->adminNote = $adminNote; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getProcessedAt(): ?\DateTimeImmutable { return $this->processedAt; }
    public function setProcessedAt(?\DateTimeImmutable $processedAt): static { $this->processedAt = $processedAt; return $this; }
}
