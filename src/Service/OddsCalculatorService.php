<?php

namespace App\Service;

use App\Entity\Bet;
use App\Entity\SportEvent;
use Doctrine\ORM\EntityManagerInterface;

class OddsCalculatorService
{
    private const MARGIN     = 0.05;   // 5% house cut
    private const SEED       = 200.0;  // virtual base per outcome
    private const MIN_ODDS   = 1.05;
    private const MAX_ODDS   = 50.0;

    public function __construct(private readonly EntityManagerInterface $em) {}

    public function recalculate(SportEvent $event): void
    {
        $outcomes = $event->getOutcomes()->toArray();

        if (!$outcomes) {
            return;
        }

        $pools = [];
        $total = 0.0;

        foreach ($outcomes as $outcome) {
            $pool = self::SEED;
            foreach ($outcome->getBets() as $bet) {
                if ($bet->getStatus() !== Bet::STATUS_ANNULE) {
                    $pool += (float) $bet->getAmount();
                }
            }
            $pools[$outcome->getId()] = $pool;
            $total += $pool;
        }

        foreach ($outcomes as $outcome) {
            $odds = ($total * (1 - self::MARGIN)) / $pools[$outcome->getId()];
            $odds = max(self::MIN_ODDS, min(self::MAX_ODDS, round($odds, 2)));
            $outcome->setOdds((string) $odds);
        }

        $this->em->flush();
    }
}
