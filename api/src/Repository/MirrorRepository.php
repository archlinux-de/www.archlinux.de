<?php

namespace App\Repository;

use App\Entity\Mirror;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class MirrorRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
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
