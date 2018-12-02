<?php

namespace App\Repository;

use App\Entity\Country;
use Doctrine\ORM\EntityRepository;

class CountryRepository extends EntityRepository
{
    /**
     * @param array $codes
     * @return Country[]
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
