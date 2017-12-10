<?php

namespace App\Repository;

use App\Entity\Release;
use Doctrine\ORM\EntityRepository;

class ReleaseRepository extends EntityRepository
{
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
}
