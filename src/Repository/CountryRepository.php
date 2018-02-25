<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class CountryRepository extends EntityRepository
{
    /**
     * @param array $codes
     * @return array
     */
    public function findAllExceptByCodes(array $codes): array
    {
        return $this
            ->createQueryBuilder('country')
            ->where('country.code NOT IN (:codes)')
            ->setParameter('codes', $codes)
            ->getQuery()
            ->getResult();
    }
}
