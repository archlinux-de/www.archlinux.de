<?php

namespace App\Entity\Packages;

use Doctrine\ORM\EntityRepository;

class PackageRepository extends EntityRepository
{
    /**
     * @param Repository $repository
     * @param $name
     * @return Package|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findByRepositoryAndName(Repository $repository, $name): ?Package
    {
        return $this
            ->createQueryBuilder('package')
            ->where('package.repository = :repository')
            ->andWhere('package.name = :name')
            ->setParameter('repository', $repository)
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @deprecated
     * @param Package $package
     * @param string $relationType
     * @return array
     */
    public function findByRelation(Package $package, string $relationType): array
    {
        return $this
            ->createQueryBuilder('target')
            ->from('App:Packages\Package', 'source')
            ->from($relationType, 'relation')
            ->where('source.repository = target.repository')
            ->andWhere('relation.targetName = target.name')
            ->andWhere('relation.source = source')
            ->andWhere('source = :source')
            ->setParameter('source', $package)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @param Package $package
     * @param string $relationType
     * @return array
     */
    public function findByInverseRelation(Package $package, string $relationType): array
    {
        return $this
            ->createQueryBuilder('source')
            ->from('App:Packages\Package', 'target')
            ->from($relationType, 'relation')
            ->where('target.repository = source.repository')
            ->andWhere('relation.target = source')
            ->andWhere('relation.source = target')
            ->andWhere('target = :target')
            ->setParameter('target', $package)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Repository $repository
     * @return \DateTime|null
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getMaxMTimeByRepository(Repository $repository): ?\DateTime
    {
        $mtime = $this
            ->createQueryBuilder('package')
            ->select('MAX(package.mTime)')
            ->where('package.repository = :repository')
            ->setParameter('repository', $repository)
            ->getQuery()
            ->getSingleScalarResult();
        if (!is_null($mtime)) {
            $mtime = new \DateTime($mtime);
        }
        return $mtime;
    }

    /**
     * @param Repository $repository
     * @param \DateTime $mTime
     * @return array
     */
    public function findByRepositoryOlderThan(Repository $repository, \DateTime $mTime): array
    {
        return $this
            ->createQueryBuilder('package')
            ->where('package.repository = :repository')
            ->andWhere('package.mTime <= :mtime')
            ->setParameter('repository', $repository)
            ->setParameter('mtime', $mTime)
            ->getQuery()
            ->getArrayResult();
    }
}
