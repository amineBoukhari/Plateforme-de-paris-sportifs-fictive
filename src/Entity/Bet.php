<?php

namespace App\Entity;

use App\Repository\BetRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BetRepository::class)]
class Bet
{
    public const STATUS_EN_ATTENTE = 'EN_ATTENTE';
    public const STATUS_GAGNE      = 'GAGNE';
    public const STATUS_PERDU      = 'PERDU';
    public const STATUS_ANNULE     = 'ANNULE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'bets')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: SportEvent::class, inversedBy: 'bets')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SportEvent $sportEvent = null;

    #[ORM\ManyToOne(targetEntity: Outcome::class, inversedBy: 'bets')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Outcome $outcome = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\GreaterThan(value: 0)]
    private ?string $amount = null;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    private ?string $oddsAtBet = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_EN_ATTENTE;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getSportEvent(): ?SportEvent
    {
        return $this->sportEvent;
    }

    public function setSportEvent(?SportEvent $sportEvent): static
    {
        $this->sportEvent = $sportEvent;
        return $this;
    }

    public function getOutcome(): ?Outcome
    {
        return $this->outcome;
    }

    public function setOutcome(?Outcome $outcome): static
    {
        $this->outcome = $outcome;
        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;
        return $this;
    }

    public function getOddsAtBet(): ?string
    {
        return $this->oddsAtBet;
    }

    public function setOddsAtBet(string $oddsAtBet): static
    {
        $this->oddsAtBet = $oddsAtBet;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }
}
