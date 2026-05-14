<?php

namespace App\Entity;

use App\Repository\TicketMessageRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: TicketMessageRepository::class)]
class TicketMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['ticket:messages'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Ticket::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Ticket $ticket = null;

    /** true = sent by admin, false = sent by user */
    #[ORM\Column]
    #[Groups(['ticket:messages'])]
    private bool $isAdmin = false;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['ticket:messages'])]
    private ?string $content = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['ticket:messages'])]
    private ?string $attachmentPath = null;

    #[ORM\Column]
    #[Groups(['ticket:messages'])]
    private bool $isRead = false;

    #[ORM\Column]
    #[Groups(['ticket:messages'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getTicket(): ?Ticket { return $this->ticket; }
    public function setTicket(?Ticket $t): static { $this->ticket = $t; return $this; }

    public function isAdmin(): bool { return $this->isAdmin; }
    public function setIsAdmin(bool $a): static { $this->isAdmin = $a; return $this; }

    public function getContent(): ?string { return $this->content; }
    public function setContent(?string $c): static { $this->content = $c; return $this; }

    public function getAttachmentPath(): ?string { return $this->attachmentPath; }
    public function setAttachmentPath(?string $p): static { $this->attachmentPath = $p; return $this; }

    public function isRead(): bool { return $this->isRead; }
    public function setIsRead(bool $r): static { $this->isRead = $r; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
