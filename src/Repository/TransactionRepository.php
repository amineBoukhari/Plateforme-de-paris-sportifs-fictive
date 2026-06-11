<?php

namespace App\Repository;

use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function sumDepositsSince(int $userId, \DateTimeImmutable $since): string
    {
        $result = $this->createQueryBuilder('t')
            ->select('SUM(t.amount)')
            ->where('t.user = :userId')
            ->andWhere('t.type = :type')
            ->andWhere('t.createdAt >= :since')
            ->setParameter('userId', $userId)
            ->setParameter('type', Transaction::TYPE_DEPOT)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? '0.00';
    }
}
