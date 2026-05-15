<?php

namespace App\Entity;

use App\Repository\PromoCodeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PromoCodeRepository::class)]
class PromoCode
{
    public const TYPE_PERCENT = 'percent';  // e.g. 10 → 10% off
    public const TYPE_FIXED   = 'fixed';    // e.g. 5  → 5 TND off

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['promo:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 30, unique: true)]
    #[Groups(['promo:read'])]
    private string $code = '';

    #[ORM\Column(length: 10)]
    #[Groups(['promo:read'])]
    private string $type = self::TYPE_PERCENT;

    /** Percentage (0-100) or fixed amount */
    #[ORM\Column(type: 'decimal', precision: 8, scale: 2)]
    #[Groups(['promo:read'])]
    private string $value = '0';

    #[ORM\Column(nullable: true)]
    #[Groups(['promo:read'])]
    private ?int $maxUses = null;

    #[ORM\Column]
    #[Groups(['promo:read'])]
    private int $usedCount = 0;

    #[ORM\Column(nullable: true)]
    #[Groups(['promo:read'])]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column]
    #[Groups(['promo:read'])]
    private bool $isActive = true;

    #[ORM\Column]
    #[Groups(['promo:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getCode(): string { return $this->code; }
    public function setCode(string $code): static { $this->code = strtoupper(trim($code)); return $this; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }

    public function getValue(): string { return $this->value; }
    public function setValue(string $value): static { $this->value = $value; return $this; }

    public function getMaxUses(): ?int { return $this->maxUses; }
    public function setMaxUses(?int $maxUses): static { $this->maxUses = $maxUses; return $this; }

    public function getUsedCount(): int { return $this->usedCount; }
    public function incrementUsed(): static { $this->usedCount++; return $this; }

    public function getExpiresAt(): ?\DateTimeImmutable { return $this->expiresAt; }
    public function setExpiresAt(?\DateTimeImmutable $expiresAt): static { $this->expiresAt = $expiresAt; return $this; }

    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): static { $this->isActive = $isActive; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function isValid(): bool
    {
        if (!$this->isActive) return false;
        if ($this->expiresAt && $this->expiresAt < new \DateTimeImmutable()) return false;
        if ($this->maxUses !== null && $this->usedCount >= $this->maxUses) return false;
        return true;
    }

    /** Compute discount amount for a given total */
    public function computeDiscount(float $total): float
    {
        $val = (float) $this->value;
        if ($this->type === self::TYPE_PERCENT) {
            return round($total * $val / 100, 2);
        }
        return min($val, $total); // fixed: never exceed total
    }
}
