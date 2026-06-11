<?php

namespace App\Entity;

use App\Repository\LimitConfigRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LimitConfigRepository::class)]
class LimitConfig
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'limitConfig')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero]
    private ?string $depositDaily = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero]
    private ?string $depositWeekly = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero]
    private ?string $betDaily = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero]
    private ?string $betWeekly = null;

    /**
     * Stores pending limit increases: ['field' => value, 'appliesAt' => ISO8601 timestamp]
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $pendingIncrease = null;

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

    public function getDepositDaily(): ?string
    {
        return $this->depositDaily;
    }

    public function setDepositDaily(?string $depositDaily): static
    {
        $this->depositDaily = $depositDaily;
        return $this;
    }

    public function getDepositWeekly(): ?string
    {
        return $this->depositWeekly;
    }

    public function setDepositWeekly(?string $depositWeekly): static
    {
        $this->depositWeekly = $depositWeekly;
        return $this;
    }

    public function getBetDaily(): ?string
    {
        return $this->betDaily;
    }

    public function setBetDaily(?string $betDaily): static
    {
        $this->betDaily = $betDaily;
        return $this;
    }

    public function getBetWeekly(): ?string
    {
        return $this->betWeekly;
    }

    public function setBetWeekly(?string $betWeekly): static
    {
        $this->betWeekly = $betWeekly;
        return $this;
    }

    public function getPendingIncrease(): ?array
    {
        return $this->pendingIncrease;
    }

    public function setPendingIncrease(?array $pendingIncrease): static
    {
        $this->pendingIncrease = $pendingIncrease;
        return $this;
    }
}
