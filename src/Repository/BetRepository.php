<?php

namespace App\Repository;

use App\Entity\Bet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Bet>
 */
class BetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bet::class);
    }

    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPendingByEvent(int $eventId): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.sportEvent = :eventId')
            ->andWhere('b.status = :status')
            ->setParameter('eventId', $eventId)
            ->setParameter('status', Bet::STATUS_EN_ATTENTE)
            ->getQuery()
            ->getResult();
    }

    public function sumBetsSince(int $userId, \DateTimeImmutable $since): float
    {
        $result = $this->createQueryBuilder('b')
            ->select('SUM(b.amount)')
            ->where('b.user = :userId')
            ->andWhere('b.status != :cancelled')
            ->andWhere('b.createdAt >= :since')
            ->setParameter('userId', $userId)
            ->setParameter('cancelled', Bet::STATUS_ANNULE)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }
}
