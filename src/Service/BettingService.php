<?php

namespace App\Service;

use App\Entity\Bet;
use App\Entity\Outcome;
use App\Entity\SportEvent;
use App\Entity\User;
use App\Repository\BetRepository;
use Doctrine\ORM\EntityManagerInterface;

class BettingService
{
    public function __construct(
        private readonly WalletService          $wallet,
        private readonly OddsCalculatorService  $oddsCalculator,
        private readonly BetRepository          $betRepository,
        private readonly EntityManagerInterface $em,
    ) {}

    public function place(User $user, Outcome $outcome, float $amount): Bet
    {
        $event = $outcome->getSportEvent();

        // US-32 — événement non publié
        if ($event->getStatus() !== SportEvent::STATUS_PUBLIE) {
            throw new \LogicException('Cet événement n\'accepte plus de paris.');
        }

        // US-32 — date dépassée
        if ($event->getEventDate() < new \DateTime()) {
            throw new \LogicException('La date de cet événement est dépassée.');
        }

        // US-32 — montant invalide
        if ($amount <= 0) {
            throw new \LogicException('Le montant doit être supérieur à 0.');
        }

        // US-33 — plafonds de mise
        $this->checkBetLimits($user, $amount);

        // Débit portefeuille (vérifie solde insuffisant)
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

    private function checkBetLimits(User $user, float $amount): void
    {
        $limit = $user->getLimitConfig();
        if ($limit === null) {
            return;
        }

        $now = new \DateTimeImmutable();

        if ($limit->getBetDaily() !== null) {
            $startOfDay  = $now->setTime(0, 0, 0);
            $totalToday  = $this->betRepository->sumBetsSince($user->getId(), $startOfDay);
            if ($totalToday + $amount > (float) $limit->getBetDaily()) {
                throw new \LogicException(sprintf(
                    'Plafond de mise quotidien atteint (%.2f €).',
                    (float) $limit->getBetDaily()
                ));
            }
        }

        if ($limit->getBetWeekly() !== null) {
            $startOfWeek  = $now->modify('monday this week')->setTime(0, 0, 0);
            $totalWeek    = $this->betRepository->sumBetsSince($user->getId(), $startOfWeek);
            if ($totalWeek + $amount > (float) $limit->getBetWeekly()) {
                throw new \LogicException(sprintf(
                    'Plafond de mise hebdomadaire atteint (%.2f €).',
                    (float) $limit->getBetWeekly()
                ));
            }
        }
    }
}
