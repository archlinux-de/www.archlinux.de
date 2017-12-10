<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class MirrorRepository extends EntityRepository
{
    private const PROTOCOL = 'https';

    /**
     * @param string $countryCode
     * @param \DateTime $lastSync
     * @return array
     */
    public function findBestByCountryAndLastSync(string $countryCode, \DateTime $lastSync): array
    {
        $mirrors = $this->findByCountryAndLastSync($countryCode, $lastSync);
        if (empty($mirrors)) {
            $mirrors = $this->findByLastSync($lastSync);
        }
        if (empty($mirrors)) {
            $mirrors = $this->findByCountry($countryCode);
        }
        if (empty($mirrors)) {
            $mirrors = $this->findSecure();
        }
        return $mirrors;
    }

    /**
     * @param string $countryCode
     * @param \DateTime $lastSync
     * @return array
     */
    private function findByCountryAndLastSync(string $countryCode, \DateTime $lastSync): array
    {
        return $this->createQueryBuilder('mirror')
            ->where('mirror.protocol = :protocol')
            ->andWhere('mirror.country = :country')
            ->andWhere('mirror.lastSync > :lastsync')
            ->orderBy('mirror.score')
            ->setParameter('protocol', self::PROTOCOL)
            ->setParameter('country', $countryCode)
            ->setParameter('lastsync', $lastSync)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param \DateTime $lastSync
     * @return array
     */
    private function findByLastSync(\DateTime $lastSync): array
    {
        return $this->createQueryBuilder('mirror')
            ->where('mirror.protocol = :protocol')
            ->andWhere('mirror.lastSync > :lastsync')
            ->orderBy('mirror.score')
            ->setParameter('protocol', self::PROTOCOL)
            ->setParameter('lastsync', $lastSync)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string $countryCode
     * @return array
     */
    private function findByCountry(string $countryCode): array
    {
        return $this->createQueryBuilder('mirror')
            ->where('mirror.protocol = :protocol')
            ->andWhere('mirror.country = :country')
            ->orderBy('mirror.score')
            ->setParameter('protocol', self::PROTOCOL)
            ->setParameter('country', $countryCode)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array
     */
    public function findSecure(): array
    {
        return $this->findBy(['protocol' => self::PROTOCOL], ['score' => 'DESC']);
    }
}
