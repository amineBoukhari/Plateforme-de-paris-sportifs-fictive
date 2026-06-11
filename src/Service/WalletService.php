<?php

namespace App\Service;

use App\Entity\Transaction;
use App\Entity\User;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;

class WalletService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TransactionRepository  $transactionRepository,
    ) {}

    public function getBalance(User $user): float
    {
        return (float) $user->getWallet();
    }

    public function deposit(User $user, float $amount): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Le montant du dépôt doit être positif.');
        }

        // US-14 / US-40 — vérification des plafonds de dépôt
        $this->checkDepositLimits($user, $amount);

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

    private function checkDepositLimits(User $user, float $amount): void
    {
        $limit = $user->getLimitConfig();
        if ($limit === null) {
            return;
        }

        $now = new \DateTimeImmutable();

        if ($limit->getDepositDaily() !== null) {
            $startOfDay  = $now->setTime(0, 0, 0);
            $totalToday  = $this->transactionRepository->sumDepositsSince($user->getId(), $startOfDay);
            if ((float) $totalToday + $amount > (float) $limit->getDepositDaily()) {
                throw new \LogicException(sprintf(
                    'Plafond de dépôt quotidien atteint (%.2f €).',
                    (float) $limit->getDepositDaily()
                ));
            }
        }

        if ($limit->getDepositWeekly() !== null) {
            $startOfWeek = $now->modify('monday this week')->setTime(0, 0, 0);
            $totalWeek   = $this->transactionRepository->sumDepositsSince($user->getId(), $startOfWeek);
            if ((float) $totalWeek + $amount > (float) $limit->getDepositWeekly()) {
                throw new \LogicException(sprintf(
                    'Plafond de dépôt hebdomadaire atteint (%.2f €).',
                    (float) $limit->getDepositWeekly()
                ));
            }
        }
    }
}
