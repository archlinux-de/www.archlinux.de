<?php

namespace App\Repository;

use App\Entity\Packages\Package;
use App\Entity\Packages\Relations\AbstractRelation;
use App\Entity\Packages\Relations\CheckDependency;
use App\Entity\Packages\Relations\Conflict;
use App\Entity\Packages\Relations\Dependency;
use App\Entity\Packages\Relations\MakeDependency;
use App\Entity\Packages\Relations\OptionalDependency;
use App\Entity\Packages\Relations\Provision;
use App\Entity\Packages\Relations\RelationTarget;
use App\Entity\Packages\Relations\Replacement;
use App\Entity\Packages\Repository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NoResultException;

/**
 * @extends ServiceEntityRepository<Package>
 */
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
            ->getOneOrNullResult(AbstractQuery::HYDRATE_OBJECT);
    }

    /**
     * @param class-string<RelationTarget> $relationType
     * @return Package[]
     */
    public function findByInverseRelationType(Package $package, string $relationType): array
    {
        /** @var Package[] $packages */
        $packages = $this
            ->createQueryBuilder('source')
            ->join(Package::class, 'target')
            ->join($relationType, 'relation')
            ->andWhere('relation.target = target')
            ->andWhere('relation.source = source')
            ->andWhere('target = :target')
            ->setParameter('target', $package)
            ->getQuery()
            ->getResult();

        return $packages;
    }

    /**
     * @return Package[]
     */
    public function findLatestByArchitecture(string $architecture, int $limit): array
    {
        return $this
            ->createQueryBuilder('package')
            ->select('package', 'repository')
            ->join('package.repository', 'repository')
            ->where('repository.architecture = :architecture')
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
            ->join('package.repository', 'repository')
            ->where('repository.architecture = :architecture')
            ->andWhere('repository.testing = 0')
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
     */
    public function getByRepositoryArchitectureAndName(string $architecture, string $name): Package
    {
        return $this
            ->createQueryBuilder('package')
            ->select('package', 'repository')
            ->join('package.repository', 'repository')
            ->where('repository.architecture = :architecture')
            ->andWhere('package.name = :pkgname')
            ->orderBy('repository.testing', 'ASC')
            ->setParameter('architecture', $architecture)
            ->setParameter('pkgname', $name)
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult(AbstractQuery::HYDRATE_OBJECT);
    }

    public function getSize(): int
    {
        return (int)$this->createQueryBuilder('package')
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
     * @param class-string<RelationTarget> $type
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
        } catch (NoResultException) {
            return [];
        }

        /** @var Package[] $packages */
        $packages = $this
            ->createQueryBuilder('source')
            ->join(Package::class, 'target')
            ->join($type, 'relation')
            ->andWhere('relation.target = target')
            ->andWhere('relation.source = source')
            ->andWhere('target = :target')
            ->orderBy('source.buildDate', 'DESC')
            ->setParameter('target', $package)
            ->getQuery()
            ->getResult();

        return $packages;
    }

    /**
     * @throws NoResultException
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
            ->getSingleResult(AbstractQuery::HYDRATE_OBJECT);
    }

    /**
     * @return AbstractRelation[]
     */
    public function findRelationsByQuery(string $repository, string $architecture, string $name, string $type): array
    {
        try {
            $package = $this->getByName($repository, $architecture, $name);
        } catch (NoResultException) {
            return [];
        }

        return match ($type) {
            CheckDependency::class => $package->getCheckDependencies()->toArray(),
            Conflict::class => $package->getConflicts()->toArray(),
            Dependency::class => $package->getDependencies()->toArray(),
            MakeDependency::class => $package->getMakeDependencies()->toArray(),
            OptionalDependency::class => $package->getOptionalDependencies()->toArray(),
            Provision::class => $package->getProvisions()->toArray(),
            Replacement::class => $package->getReplacements()->toArray(),
            default => [],
        };
    }

    public function findOneByName(string $name): ?Package
    {
        return $this->createQueryBuilder('package')
            ->select('package', 'repository')
            ->join('package.repository', 'repository')
            ->where('package.name = :pkgname')
            ->andWhere('repository.testing = false')
            ->setParameter('pkgname', $name)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_OBJECT);
    }
}
