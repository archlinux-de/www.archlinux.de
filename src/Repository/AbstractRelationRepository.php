<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\OptimisticLockException;

class AbstractRelationRepository extends EntityRepository
{
    public function updateTargets()
    {
        try {
            $this->getEntityManager()->flush();
        } catch (OptimisticLockException $e) {
        }

        $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->update('packages_relation')->set('target_id', 'NULL')->execute();

        $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->update('
                packages_relation relation,
                package source,
                package target
             ')
            ->set('target_id', 'target.id')
            ->where('source.repository_id = target.repository_id')
            ->andWhere('relation.source_id = source.id')
            ->andWhere('relation.target_name = target.name')
            ->execute();

        $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->update('
                packages_relation relation,
                package source,
                package target,
                repository source_repository,
                repository target_repository
            ')
            ->set('target_id', 'target.id')
            ->where('relation.target_id IS NULL')
            ->andWhere('source.repository_id = source_repository.id')
            ->andWhere('target.repository_id = target_repository.id')
            ->andWhere('source_repository.architecture = target_repository.architecture')
            ->andWhere('target_repository.testing = 0')
            ->andWhere('relation.source_id = source.id')
            ->andWhere('relation.target_name = target.name')
            ->execute();
    }
}
