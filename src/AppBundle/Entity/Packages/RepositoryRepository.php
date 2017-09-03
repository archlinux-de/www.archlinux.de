<?php

namespace AppBundle\Entity\Packages;

use Doctrine\ORM\EntityRepository;

class RepositoryRepository extends EntityRepository
{
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
