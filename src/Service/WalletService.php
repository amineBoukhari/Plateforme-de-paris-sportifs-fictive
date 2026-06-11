<?php

namespace App\Service;

use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class WalletService
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function getBalance(User $user): float
    {
        return (float) $user->getWallet();
    }

    public function deposit(User $user, float $amount): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Le montant du dépôt doit être positif.');
        }

        $user->setWallet((string) round($this->getBalance($user) + $amount, 2));

        $transaction = (new Transaction())
            ->setUser($user)
            ->setAmount((string) $amount)
            ->setType(Transaction::TYPE_DEPOT);

        $this->em->persist($transaction);
        $this->em->flush();
    }

    public function debit(User $user, float $amount): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Le montant doit être positif.');
        }

        if ($this->getBalance($user) < $amount) {
            throw new \LogicException('Solde insuffisant.');
        }

        $user->setWallet((string) round($this->getBalance($user) - $amount, 2));

        $transaction = (new Transaction())
            ->setUser($user)
            ->setAmount((string) $amount)
            ->setType(Transaction::TYPE_MISE);

        $this->em->persist($transaction);
        $this->em->flush();
    }
}
