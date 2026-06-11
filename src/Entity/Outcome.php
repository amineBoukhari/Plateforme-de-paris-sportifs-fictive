<?php

namespace App\Entity;

use App\Repository\OutcomeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OutcomeRepository::class)]
class Outcome
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $label = null;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    #[Assert\GreaterThan(value: 1)]
    private ?string $odds = null;

    #[ORM\ManyToOne(targetEntity: SportEvent::class, inversedBy: 'outcomes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SportEvent $sportEvent = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isWinner = null;

    #[ORM\OneToMany(mappedBy: 'outcome', targetEntity: Bet::class)]
    private Collection $bets;

    public function __construct()
    {
        $this->bets = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function getOdds(): ?string
    {
        return $this->odds;
    }

    public function setOdds(string $odds): static
    {
        $this->odds = $odds;
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

    public function isWinner(): ?bool
    {
        return $this->isWinner;
    }

    public function setIsWinner(?bool $isWinner): static
    {
        $this->isWinner = $isWinner;
        return $this;
    }

    public function getBets(): Collection
    {
        return $this->bets;
    }

    public function __toString(): string
    {
        return $this->label ?? '';
    }
}
