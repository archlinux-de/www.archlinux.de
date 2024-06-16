<?php

namespace App\Repository;

use App\Entity\Country;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Country>
 */
class CountryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Country::class);
    }

    /**
     * @param string[] $codes
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
