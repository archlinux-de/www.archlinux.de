<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class CountryRepository extends EntityRepository
{
    /**
     * @param array $ids
     * @return array
     */
    public function findAllExceptByIds(array $ids): array
    {
        return $this
            ->createQueryBuilder('country')
            ->where('country.code NOT IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }
}
