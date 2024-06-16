<?php

namespace App\Repository;

use App\Entity\Packages\Repository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Repository>
 */
class RepositoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Repository::class);
    }

    public function findByNameAndArchitecture(string $repoName, string $archName): ?Repository
    {
        return $this->createQueryBuilder('repository')
            ->where('repository.name = :repository')
            ->andWhere('repository.architecture = :architecture')
            ->setParameter('repository', $repoName)
            ->setParameter('architecture', $archName)
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_OBJECT);
    }
}
