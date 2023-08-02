<?php

namespace App\Repository;

use App\Entity\NewsItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NewsItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NewsItem::class);
    }

    /**
     * @return NewsItem[]
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
     * @param int[] $ids
     * @return NewsItem[]
     */
    public function findAllExceptByIds(array $ids): array
    {
        return $this
            ->createQueryBuilder('newsItem')
            ->where('newsItem.id NOT IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    public function getSize(): int
    {
        return (int)$this->createQueryBuilder('news')
            ->select('COUNT(news)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
