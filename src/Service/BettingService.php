<?php

namespace App\Service;

use App\Entity\Bet;
use App\Entity\Outcome;
use App\Entity\SportEvent;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class BettingService
{
    public function __construct(
        private readonly WalletService          $wallet,
        private readonly OddsCalculatorService  $oddsCalculator,
        private readonly EntityManagerInterface $em,
    ) {}

    public function place(User $user, Outcome $outcome, float $amount): Bet
    {
        $event = $outcome->getSportEvent();

        if ($event->getStatus() !== SportEvent::STATUS_PUBLIE) {
            throw new \LogicException('Cet événement n\'accepte plus de paris.');
        }

        if (!$user->isActive()) {
            throw new \LogicException('Votre compte est suspendu.');
        }

        // Debit wallet — throws \LogicException if balance insufficient
        $this->wallet->debit($user, $amount);

        $bet = (new Bet())
            ->setUser($user)
            ->setSportEvent($event)
            ->setOutcome($outcome)
            ->setAmount((string) $amount)
            ->setOddsAtBet($outcome->getOdds());

        $this->em->persist($bet);
        $this->em->flush();

        $this->oddsCalculator->recalculate($event);

        return $bet;
    }
}
