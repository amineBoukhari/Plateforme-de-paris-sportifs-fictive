<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(type: 'date')]
    #[Assert\NotBlank]
    private \DateTimeInterface $birthdate;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $wallet = '0.00';

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Bet::class, orphanRemoval: true)]
    private Collection $bets;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Transaction::class, orphanRemoval: true)]
    private Collection $transactions;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: LimitConfig::class, cascade: ['persist', 'remove'])]
    private ?LimitConfig $limitConfig = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: SelfExclusion::class, orphanRemoval: true)]
    private Collection $selfExclusions;

    #[ORM\OneToMany(mappedBy: 'manager', targetEntity: SportEvent::class)]
    private Collection $managedEvents;

    public function __construct()
    {
        $this->bets = new ArrayCollection();
        $this->transactions = new ArrayCollection();
        $this->selfExclusions = new ArrayCollection();
        $this->managedEvents = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void {}

    public function getBirthdate(): ?\DateTimeInterface
    {
        return $this->birthdate;
    }

    public function setBirthdate(\DateTimeInterface $birthdate): static
    {
        $this->birthdate = $birthdate;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getWallet(): string
    {
        return $this->wallet;
    }

    public function setWallet(string $wallet): static
    {
        $this->wallet = $wallet;
        return $this;
    }

    public function getBets(): Collection
    {
        return $this->bets;
    }

    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function getLimitConfig(): ?LimitConfig
    {
        return $this->limitConfig;
    }

    public function setLimitConfig(?LimitConfig $limitConfig): static
    {
        if ($limitConfig === null && $this->limitConfig !== null) {
            $this->limitConfig->setUser(null);
        }
        if ($limitConfig !== null && $limitConfig->getUser() !== $this) {
            $limitConfig->setUser($this);
        }
        $this->limitConfig = $limitConfig;
        return $this;
    }

    public function getSelfExclusions(): Collection
    {
        return $this->selfExclusions;
    }

    public function getManagedEvents(): Collection
    {
        return $this->managedEvents;
    }

    public function __toString(): string
    {
        return $this->email ?? '';
    }
}
