<?php

namespace App\Repository;

use App\Entity\Mirror;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Mirror>
 */
class MirrorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Mirror::class);
    }

    /**
     * @param string[] $urls
     * @return Mirror[]
     */
    public function findAllExceptByUrls(array $urls): array
    {
        return $this
            ->createQueryBuilder('mirror')
            ->where('mirror.url NOT IN (:urls)')
            ->setParameter('urls', $urls)
            ->getQuery()
            ->getResult();
    }
}
