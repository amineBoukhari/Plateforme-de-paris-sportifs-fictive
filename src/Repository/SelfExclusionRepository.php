<?php

namespace App\Repository;

use App\Entity\SelfExclusion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SelfExclusion>
 */
class SelfExclusionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SelfExclusion::class);
    }

    public function findActiveByUser(int $userId): ?SelfExclusion
    {
        $now = new \DateTimeImmutable();

        return $this->createQueryBuilder('s')
            ->where('s.user = :userId')
            ->andWhere('s.startDate <= :now')
            ->andWhere('s.endDate >= :now')
            ->setParameter('userId', $userId)
            ->setParameter('now', $now)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
