<?php

namespace App\Repository;

use App\Entity\Packages\Repository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RepositoryRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Repository::class);
    }

    /**
     * @param string $repoName
     * @param string $archName
     * @return Repository|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findByNameAndArchitecture(string $repoName, string $archName): ?Repository
    {
        return $this->createQueryBuilder('repository')
            ->where('repository.name = :repository')
            ->andWhere('repository.architecture = :architecture')
            ->setParameter('repository', $repoName)
            ->setParameter('architecture', $archName)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
