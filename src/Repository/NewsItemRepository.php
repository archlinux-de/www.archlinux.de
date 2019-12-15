<?php

namespace App\Repository;

use App\Entity\NewsItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class NewsItemRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NewsItem::class);
    }

    /**
     * @param int $limit
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
     * @param string[] $ids
     * @param \DateTime $lastModified
     * @return NewsItem[]
     */
    public function findAllExceptByIdsNewerThan(array $ids, \DateTime $lastModified): array
    {
        return $this
            ->createQueryBuilder('newsItem')
            ->where('newsItem.id NOT IN (:ids)')
            ->andWhere('newsItem.lastModified >= :lastModified')
            ->setParameter('ids', $ids)
            ->setParameter('lastModified', $lastModified)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return int
     */
    public function getSize(): int
    {
        return $this->createQueryBuilder('news')
            ->select('COUNT(news)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
