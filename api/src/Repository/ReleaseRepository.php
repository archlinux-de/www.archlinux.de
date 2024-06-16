<?php

namespace App\Repository;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\NoResultException;
use App\Entity\Release;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Release>
 */
class ReleaseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Release::class);
    }

    /**
     * @throws NoResultException
     */
    public function getLatestAvailable(): Release
    {
        return $this
            ->createQueryBuilder('release')
            ->where('release.available = true')
            ->orderBy('release.releaseDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult(AbstractQuery::HYDRATE_OBJECT);
    }

    /**
     * @throws NoResultException
     */
    public function getByVersion(string $version): Release
    {
        return $this
            ->createQueryBuilder('release')
            ->where('release.version = :version')
            ->setParameter('version', $version)
            ->getQuery()
            ->getSingleResult(AbstractQuery::HYDRATE_OBJECT);
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

    public function getSize(): int
    {
        return (int)$this->createQueryBuilder('release')
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
}
