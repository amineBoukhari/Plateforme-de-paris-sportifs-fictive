<?php

namespace App\Service;

use App\Entity\Bet;
use App\Entity\Outcome;
use App\Entity\SportEvent;
use App\Entity\Transaction;
use App\Repository\BetRepository;
use Doctrine\ORM\EntityManagerInterface;

class PayoutService
{
    public function __construct(
        private readonly BetRepository          $betRepository,
        private readonly EntityManagerInterface $em,
    ) {}

    // US-56 — Crédite les gagnants et marque les perdants
    public function payout(SportEvent $event, Outcome $winningOutcome): void
    {
        $pendingBets = $this->betRepository->findPendingByEvent($event->getId());

        foreach ($pendingBets as $bet) {
            if ($bet->getOutcome()->getId() === $winningOutcome->getId()) {
                $gain = round((float) $bet->getAmount() * (float) $bet->getOddsAtBet(), 2);

                $user = $bet->getUser();
                $user->setWallet((string) round((float) $user->getWallet() + $gain, 2));

                $transaction = (new Transaction())
                    ->setUser($user)
                    ->setAmount((string) $gain)
                    ->setType(Transaction::TYPE_GAIN);

                $this->em->persist($transaction);
                $bet->setStatus(Bet::STATUS_GAGNE);
            } else {
                $bet->setStatus(Bet::STATUS_PERDU);
            }
        }

        $this->em->flush();
    }

    // US-57 — Rembourse tous les paris EN_ATTENTE d'un événement annulé
    public function refund(SportEvent $event): void
    {
        $pendingBets = $this->betRepository->findPendingByEvent($event->getId());

        foreach ($pendingBets as $bet) {
            $user   = $bet->getUser();
            $amount = (float) $bet->getAmount();

            $user->setWallet((string) round((float) $user->getWallet() + $amount, 2));

            $transaction = (new Transaction())
                ->setUser($user)
                ->setAmount((string) $amount)
                ->setType(Transaction::TYPE_REMBOURSEMENT);

            $this->em->persist($transaction);
            $bet->setStatus(Bet::STATUS_ANNULE);
        }

        $this->em->flush();
    }
}
