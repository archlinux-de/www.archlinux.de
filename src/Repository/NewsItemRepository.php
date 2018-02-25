<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class NewsItemRepository extends EntityRepository
{
    /**
     * @param int $limit
     * @return array
     */
    public function findLatest(int $limit): array
    {
        return $this
            ->createQueryBuilder('newsItem')
            ->orderBy('newsItem.lastModified', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array $ids
     * @param \DateTime $lastModified
     * @return array
     */
    public function findAllExceptByIdsNewerThan(array $ids, \DateTime $lastModified): array
    {
        return $this
            ->createQueryBuilder('newsItem')
            ->where('newsItem.id NOT IN (:ids)')
            ->andWhere('newsItem.lastModified > :lastModified')
            ->setParameter('ids', $ids)
            ->setParameter('lastModified', $lastModified)
            ->getQuery()
            ->getResult();
    }
}
