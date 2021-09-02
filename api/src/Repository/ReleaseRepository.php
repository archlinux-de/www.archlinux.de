<?php

namespace App\Repository;

use Doctrine\ORM\NoResultException;
use Doctrine\ORM\NonUniqueResultException;
use App\Entity\Release;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
     * @throws NoResultException
     * @throws NonUniqueResultException
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
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getByVersion(string $version): Release
    {
        return $this
            ->createQueryBuilder('release')
            ->where('release.version = :version')
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
}
