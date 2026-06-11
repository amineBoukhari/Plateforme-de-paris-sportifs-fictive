<?php

namespace App\Repository;

use App\Entity\SportEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SportEvent>
 */
class SportEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SportEvent::class);
    }

    public function findPublished(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.status = :status')
            ->setParameter('status', SportEvent::STATUS_PUBLIE)
            ->orderBy('e.eventDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByManager(int $managerId): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.manager = :managerId')
            ->setParameter('managerId', $managerId)
            ->orderBy('e.eventDate', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
