<?php

namespace App\Repository;

use App\Entity\Packages\Package;
use App\Entity\Packages\Repository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Tools\Pagination\Paginator;

class PackageRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Package::class);
    }

    /**
     * @param Repository $repository
     * @param string $name
     * @return Package|null
     */
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
     * @param Package $package
     * @param string $relationType
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
     * @param string $architecture
     * @param int $limit
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
     * @param int $offset
     * @param int $limit
     * @param string $query
     * @param string $architecture
     * @param string|null $repository
     * @return Paginator<Package>
     */
    public function findLatestByQueryAndArchitecture(
        int $offset,
        int $limit,
        string $query,
        string $architecture,
        ?string $repository
    ): Paginator {
        $queryBuilder = $this
            ->createQueryBuilder('package')
            ->select('package', 'repository');
        if ($repository) {
            $queryBuilder->join(
                'package.repository',
                'repository',
                'WITH',
                'repository.architecture = :architecture AND repository.name = :repository'
            )
                ->setParameter('repository', $repository);
        } else {
            $queryBuilder->join(
                'package.repository',
                'repository',
                'WITH',
                'repository.architecture = :architecture'
            );
        }
        $queryBuilder
            ->orderBy('package.buildDate', 'DESC')
            ->setParameter('architecture', $architecture)
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        if ($query) {
            $queryBuilder
                ->where('package.name LIKE :query')
                ->orWhere('package.description LIKE :query')
                ->setParameter('query', '%' . $query . '%');
        }

        return new Paginator($queryBuilder);
    }

    /**
     * @param string $architecture
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
     * @param string $term
     * @param int $limit
     * @return Package[]
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
     * @param string $architecture
     * @param string $name
     * @return Package
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
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

    /**
     * @return int
     */
    public function getSize(): int
    {
        return $this->createQueryBuilder('package')
            ->select('COUNT(package)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param Repository $repository
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
     * @param Repository $repository
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
     * @param string $repo
     * @param string $arch
     * @param string $pkgname
     * @param string $type
     * @return Package[]
     */
    public function findInverseRelationsByQuery(
        string $repo,
        string $arch,
        string $pkgname,
        string $type
    ): array {
        try {
            $package = $this->getByName($repo, $arch, $pkgname);
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
}
