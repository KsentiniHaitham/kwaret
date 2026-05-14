<?php

namespace App\Entity;

use App\Repository\TicketRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: TicketRepository::class)]
class Ticket
{
    const STATUS_OPEN   = 'open';
    const STATUS_CLOSED = 'closed';

    const TYPE_RECHARGE = 'recharge';
    const TYPE_ORDER    = 'order';
    const TYPE_SUPPORT  = 'support';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['ticket:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['ticket:read'])]
    private ?User $user = null;

    #[ORM\Column(length: 100)]
    #[Groups(['ticket:read'])]
    private string $subject = '';

    #[ORM\Column(length: 20)]
    #[Groups(['ticket:read'])]
    private string $type = self::TYPE_SUPPORT;

    #[ORM\Column(nullable: true)]
    #[Groups(['ticket:read'])]
    private ?int $referenceId = null;

    #[ORM\Column(length: 20)]
    #[Groups(['ticket:read'])]
    private string $status = self::STATUS_OPEN;

    #[ORM\Column(nullable: true)]
    #[Groups(['ticket:read'])]
    private ?int $rating = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['ticket:read'])]
    private ?string $ratingComment = null;

    #[ORM\Column]
    #[Groups(['ticket:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    #[Groups(['ticket:read'])]
    private ?\DateTimeImmutable $closedAt = null;

    #[ORM\OneToMany(mappedBy: 'ticket', targetEntity: TicketMessage::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    #[Groups(['ticket:messages'])]
    private Collection $messages;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->messages  = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getSubject(): string { return $this->subject; }
    public function setSubject(string $s): static { $this->subject = $s; return $this; }

    public function getType(): string { return $this->type; }
    public function setType(string $t): static { $this->type = $t; return $this; }

    public function getReferenceId(): ?int { return $this->referenceId; }
    public function setReferenceId(?int $id): static { $this->referenceId = $id; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $s): static { $this->status = $s; return $this; }

    public function getRating(): ?int { return $this->rating; }
    public function setRating(?int $r): static { $this->rating = $r; return $this; }

    public function getRatingComment(): ?string { return $this->ratingComment; }
    public function setRatingComment(?string $c): static { $this->ratingComment = $c; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getClosedAt(): ?\DateTimeImmutable { return $this->closedAt; }
    public function setClosedAt(?\DateTimeImmutable $d): static { $this->closedAt = $d; return $this; }

    public function getMessages(): Collection { return $this->messages; }

    /** Count unread messages for given side (isAdmin = true → user reads admin msgs) */
    public function countUnreadFor(bool $isAdmin): int
    {
        return $this->messages->filter(
            fn($m) => !$m->isRead() && $m->isAdmin() !== $isAdmin
        )->count();
    }
}
