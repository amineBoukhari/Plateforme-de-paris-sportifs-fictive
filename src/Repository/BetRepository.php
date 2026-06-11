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
}
