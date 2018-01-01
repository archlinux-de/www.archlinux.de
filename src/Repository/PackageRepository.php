<?php

namespace App\Repository;

use App\Entity\Packages\Package;
use App\Entity\Packages\Repository;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\UnexpectedResultException;

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
            ->getResult();
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
            ->getResult();
    }

    /**
     * @param string $architecture
     * @param int $limit
     * @return array
     */
    public function findLatestByArchitecture(string $architecture, int $limit): array
    {
        return $this
            ->createQueryBuilder('package')
            ->select('package', 'repository')
            ->join('package.repository', 'repository', 'WITH', 'repository.architecture = :architecture')
            ->orderBy('package.buildDate', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('architecture', $architecture)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string $architecture
     * @return array
     */
    public function findStableByArchitecture(string $architecture): array
    {
        return $this
            ->createQueryBuilder('package')
            ->select('package', 'repository')
            ->join('package.repository', 'repository', 'WITH', 'repository.architecture = :architecture')
            ->where('repository.testing = 0')
            ->setParameter('architecture', $architecture)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string $term
     * @param int $limit
     * @return array
     */
    public function findByTerm(string $term, int $limit): array
    {
        return $this
            ->createQueryBuilder('package')
            ->select('package.name')
            ->distinct()
            ->where('package.name LIKE :package')
            ->orderBy('package.name')
            ->setMaxResults($limit)
            ->setParameter('package', $term . '%')
            ->getQuery()
            ->getScalarResult();
    }

    /**
     * @param string $repository
     * @param string $architecture
     * @param string $name
     * @return Package
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getByName(string $repository, string $architecture, string $name): Package
    {
        return $this->createQueryBuilder('package')
            ->select('package', 'repository')
            ->join('package.repository', 'repository')
            ->where('package.name = :pkgname')
            ->andWhere('repository.name = :repository')
            ->andWhere('repository.architecture = :architecture')
            ->setParameter('pkgname', $name)
            ->setParameter('repository', $repository)
            ->setParameter('architecture', $architecture)
            ->getQuery()
            ->getSingleResult();
    }

    /**
     * @return int
     */
    public function getSize(): int
    {
        try {
            return $this->createQueryBuilder('package')
                ->select('COUNT(package)')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (UnexpectedResultException $e) {
            return 0;
        }
    }
}
