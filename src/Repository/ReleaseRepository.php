<?php

namespace App\Repository;

use App\Entity\Release;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

class ReleaseRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Release::class);
    }

    /**
     * @return Release
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLatestAvailable(): Release
    {
        return $this
            ->createQueryBuilder('release')
            ->where('release.available = true')
            ->orderBy('release.releaseDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult();
    }

    /**
     * @param string $version
     * @return Release
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getAvailableByVersion(string $version): Release
    {
        return $this
            ->createQueryBuilder('release')
            ->where('release.available = true')
            ->andWhere('release.version = :version')
            ->setParameter('version', $version)
            ->getQuery()
            ->getSingleResult();
    }

    /**
     * @param string[] $versions
     * @return Release[]
     */
    public function findAllExceptByVersions(array $versions): array
    {
        return $this
            ->createQueryBuilder('release')
            ->where('release.version NOT IN (:versions)')
            ->setParameter('versions', $versions)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return int
     */
    public function getSize(): int
    {
        return $this->createQueryBuilder('release')
            ->select('COUNT(release)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Release[]
     */
    public function findAllAvailable(): array
    {
        return $this
            ->createQueryBuilder('release')
            ->where('release.available = true')
            ->orderBy('release.releaseDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param int $offset
     * @param int $limit
     * @param string $query
     * @return Paginator<Release>
     */
    public function findAllByQuery(int $offset, int $limit, string $query): Paginator
    {
        $queryBuilder = $this
            ->createQueryBuilder('release')
            ->orderBy('release.releaseDate', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        if ($query) {
            $queryBuilder
                ->where('release.version LIKE :query')
                ->orWhere('release.kernelVersion LIKE :query')
                ->orWhere('release.info LIKE :query')
                ->setParameter('query', '%' . $query . '%');
        }

        return new Paginator($queryBuilder);
    }
}
