<?php

namespace App\Repository;

use App\Entity\Packages\Files;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

class FilesRepository extends EntityRepository
{
    /**
     * @param string $repository
     * @param string $architecture
     * @param string $name
     * @return Files
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getByPackageName(string $repository, string $architecture, string $name): Files
    {
        return $this->createQueryBuilder('files')
            ->join('files.package', 'package')
            ->join('package.repository', 'repository')
            ->where('package.name = :pkgname')
            ->andWhere('repository.name = :repository')
            ->andWhere('repository.architecture = :architecture')
            ->setParameter('pkgname', $name)
            ->setParameter('repository', $repository)
            ->setParameter('architecture', $architecture)
            ->getQuery()
            ->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
            ->getSingleResult();
    }
}
