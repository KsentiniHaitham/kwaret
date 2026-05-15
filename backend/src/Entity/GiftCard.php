<?php

namespace App\Entity;

use App\Repository\GiftCardRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: GiftCardRepository::class)]
class GiftCard
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['gift:read'])]
    private ?int $id = null;

    /** Unique redemption code e.g. KWARET-XXXX-XXXX */
    #[ORM\Column(length: 20, unique: true)]
    #[Groups(['gift:read'])]
    private string $code = '';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Groups(['gift:read'])]
    private string $initialValue = '0';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Groups(['gift:read'])]
    private string $remainingValue = '0';

    /** The user who purchased the gift card (null = admin-generated) */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['gift:read'])]
    private ?User $purchasedBy = null;

    /** The user who redeemed/applied the gift card to wallet */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $redeemedBy = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['gift:read'])]
    private ?\DateTimeImmutable $redeemedAt = null;

    #[ORM\Column]
    #[Groups(['gift:read'])]
    private bool $isActive = true;

    #[ORM\Column]
    #[Groups(['gift:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->code = $this->generateCode();
    }

    private function generateCode(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $part  = fn() => implode('', array_map(fn() => $chars[random_int(0, strlen($chars)-1)], range(1,4)));
        return 'KWARET-' . $part() . '-' . $part();
    }

    public function getId(): ?int { return $this->id; }

    public function getCode(): string { return $this->code; }
    public function setCode(string $code): static { $this->code = strtoupper(trim($code)); return $this; }

    public function getInitialValue(): string { return $this->initialValue; }
    public function setInitialValue(string $v): static { $this->initialValue = $v; $this->remainingValue = $v; return $this; }

    public function getRemainingValue(): string { return $this->remainingValue; }
    public function setRemainingValue(string $v): static { $this->remainingValue = $v; return $this; }

    public function getPurchasedBy(): ?User { return $this->purchasedBy; }
    public function setPurchasedBy(?User $u): static { $this->purchasedBy = $u; return $this; }

    public function getRedeemedBy(): ?User { return $this->redeemedBy; }
    public function setRedeemedBy(?User $u): static { $this->redeemedBy = $u; return $this; }

    public function getRedeemedAt(): ?\DateTimeImmutable { return $this->redeemedAt; }
    public function setRedeemedAt(?\DateTimeImmutable $d): static { $this->redeemedAt = $d; return $this; }

    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $v): static { $this->isActive = $v; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function isRedeemed(): bool { return $this->redeemedAt !== null; }

    public function isUsable(): bool
    {
        return $this->isActive && !$this->isRedeemed() && (float)$this->remainingValue > 0;
    }
}
