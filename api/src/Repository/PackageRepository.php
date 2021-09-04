<?php

namespace App\Repository;

use Doctrine\ORM\NonUniqueResultException;
use App\Entity\Packages\Package;
use App\Entity\Packages\Relations\AbstractRelation;
use App\Entity\Packages\Relations\CheckDependency;
use App\Entity\Packages\Relations\Conflict;
use App\Entity\Packages\Relations\Dependency;
use App\Entity\Packages\Relations\MakeDependency;
use App\Entity\Packages\Relations\OptionalDependency;
use App\Entity\Packages\Relations\Provision;
use App\Entity\Packages\Relations\Replacement;
use App\Entity\Packages\Repository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NoResultException;

class PackageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Package::class);
    }

    public function findByRepositoryAndName(Repository $repository, string $name): ?Package
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
     * @return Package[]
     */
    public function findByInverseRelationType(Package $package, string $relationType): array
    {
        return $this
            ->createQueryBuilder('source')
            ->join('App:Packages\Package', 'target')
            ->join($relationType, 'relation')
            ->andWhere('relation.target = target')
            ->andWhere('relation.source = source')
            ->andWhere('target = :target')
            ->setParameter('target', $package)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Package[]
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
     * @return Package[]
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
     * @return Package[]
     */
    public function findStable(): array
    {
        return $this
            ->createQueryBuilder('package')
            ->select('package', 'repository')
            ->join('package.repository', 'repository')
            ->where('repository.testing = 0')
            ->getQuery()
            ->getResult();
    }

    /**
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getByRepositoryArchitectureAndName(string $architecture, string $name): Package
    {
        return $this
            ->createQueryBuilder('package')
            ->select('package', 'repository')
            ->join('package.repository', 'repository', 'WITH', 'repository.architecture = :architecture')
            ->where('package.name = :pkgname')
            ->orderBy('repository.testing', 'ASC')
            ->setParameter('architecture', $architecture)
            ->setParameter('pkgname', $name)
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult();
    }

    public function getSize(): int
    {
        return $this->createQueryBuilder('package')
            ->select('COUNT(package)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param string[] $packageNames
     * @return Package[]
     */
    public function findByRepositoryExceptNames(Repository $repository, array $packageNames): array
    {
        if (empty($packageNames)) {
            return $this->findByRepository($repository);
        }
        return $this
            ->createQueryBuilder('package')
            ->where('package.repository = :repository')
            ->andWhere('package.name NOT IN (:packageNames)')
            ->setParameter('repository', $repository)
            ->setParameter('packageNames', $packageNames)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Package[]
     */
    public function findByRepository(Repository $repository): array
    {
        return $this
            ->createQueryBuilder('package')
            ->where('package.repository = :repository')
            ->setParameter('repository', $repository)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Package[]
     */
    public function findInverseRelationsByQuery(
        string $repository,
        string $architecture,
        string $name,
        string $type
    ): array {
        try {
            $package = $this->getByName($repository, $architecture, $name);
        } catch (NoResultException $e) {
            return [];
        }

        return $this
            ->createQueryBuilder('source')
            ->join('App:Packages\Package', 'target')
            ->join($type, 'relation')
            ->andWhere('relation.target = target')
            ->andWhere('relation.source = source')
            ->andWhere('target = :target')
            ->orderBy('source.buildDate', 'DESC')
            ->setParameter('target', $package)
            ->getQuery()
            ->getResult();
    }

    /**
     * @throws NoResultException
     * @throws NonUniqueResultException
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
     * @return AbstractRelation[]
     */
    public function findRelationsByQuery(string $repository, string $architecture, string $name, string $type): array
    {
        try {
            $package = $this->getByName($repository, $architecture, $name);
        } catch (NoResultException $e) {
            return [];
        }

        switch ($type) {
            case CheckDependency::class:
                $dependencies = $package->getCheckDependencies()->toArray();
                break;
            case Conflict::class:
                $dependencies = $package->getConflicts()->toArray();
                break;
            case Dependency::class:
                $dependencies = $package->getDependencies()->toArray();
                break;
            case MakeDependency::class:
                $dependencies = $package->getMakeDependencies()->toArray();
                break;
            case OptionalDependency::class:
                $dependencies = $package->getOptionalDependencies()->toArray();
                break;
            case Provision::class:
                $dependencies = $package->getProvisions()->toArray();
                break;
            case Replacement::class:
                $dependencies = $package->getReplacements()->toArray();
                break;
            default:
                $dependencies = [];
        }

        return $dependencies;
    }
}
