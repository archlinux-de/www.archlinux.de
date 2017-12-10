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
            ->createQueryBuilder('news')
            ->orderBy('news.lastModified', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
