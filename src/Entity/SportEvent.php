<?php

namespace App\Entity;

use App\Repository\SportEventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SportEventRepository::class)]
class SportEvent
{
    public const STATUS_BROUILLON = 'BROUILLON';
    public const STATUS_PUBLIE    = 'PUBLIE';
    public const STATUS_FERME     = 'FERME';
    public const STATUS_TERMINE   = 'TERMINE';
    public const STATUS_ANNULE    = 'ANNULE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private ?string $sport = null;

    #[ORM\Column(type: 'json')]
    private array $participants = [];

    #[ORM\Column(type: 'datetime')]
    #[Assert\NotNull]
    private ?\DateTimeInterface $eventDate = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_BROUILLON;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'managedEvents')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $manager = null;

    #[ORM\OneToMany(mappedBy: 'sportEvent', targetEntity: Outcome::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $outcomes;

    #[ORM\OneToMany(mappedBy: 'sportEvent', targetEntity: Bet::class)]
    private Collection $bets;

    public function __construct()
    {
        $this->outcomes = new ArrayCollection();
        $this->bets = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getSport(): ?string
    {
        return $this->sport;
    }

    public function setSport(string $sport): static
    {
        $this->sport = $sport;
        return $this;
    }

    public function getParticipants(): array
    {
        return $this->participants;
    }

    public function setParticipants(array $participants): static
    {
        $this->participants = $participants;
        return $this;
    }

    public function getEventDate(): ?\DateTimeInterface
    {
        return $this->eventDate;
    }

    public function setEventDate(\DateTimeInterface $eventDate): static
    {
        $this->eventDate = $eventDate;
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

    public function getManager(): ?User
    {
        return $this->manager;
    }

    public function setManager(?User $manager): static
    {
        $this->manager = $manager;
        return $this;
    }

    public function getOutcomes(): Collection
    {
        return $this->outcomes;
    }

    public function addOutcome(Outcome $outcome): static
    {
        if (!$this->outcomes->contains($outcome)) {
            $this->outcomes->add($outcome);
            $outcome->setSportEvent($this);
        }
        return $this;
    }

    public function removeOutcome(Outcome $outcome): static
    {
        if ($this->outcomes->removeElement($outcome)) {
            if ($outcome->getSportEvent() === $this) {
                $outcome->setSportEvent(null);
            }
        }
        return $this;
    }

    public function getBets(): Collection
    {
        return $this->bets;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
