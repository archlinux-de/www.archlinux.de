<?php

namespace App\Repository;

use App\Entity\Mirror;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class MirrorRepository extends ServiceEntityRepository
{
    private const PROTOCOL = 'https';

    /**
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Mirror::class);
    }

    /**
     * @param string $countryCode
     * @param \DateTime $lastSync
     * @return Mirror[]
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
            $mirrors = $this->findActive();
        }
        return $mirrors;
    }

    /**
     * @param string $countryCode
     * @param \DateTime $lastSync
     * @return Mirror[]
     */
    private function findByCountryAndLastSync(string $countryCode, \DateTime $lastSync): array
    {
        return $this->createQueryBuilder('mirror')
            ->where('mirror.protocol = :protocol')
            ->andWhere('mirror.country = :country')
            ->andWhere('mirror.lastSync > DATE_ADD(:lastsync, mirror.delay, \'second\')')
            ->andWhere('mirror.active = true')
            ->andWhere('mirror.isos = true')
            ->orderBy('mirror.score')
            ->setParameter('protocol', self::PROTOCOL)
            ->setParameter('country', $countryCode)
            ->setParameter('lastsync', $lastSync)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param \DateTime $lastSync
     * @return Mirror[]
     */
    private function findByLastSync(\DateTime $lastSync): array
    {
        return $this->createQueryBuilder('mirror')
            ->where('mirror.protocol = :protocol')
            ->andWhere('mirror.lastSync > DATE_ADD(:lastsync, mirror.delay, \'second\')')
            ->andWhere('mirror.active = true')
            ->andWhere('mirror.isos = true')
            ->orderBy('mirror.score')
            ->setParameter('protocol', self::PROTOCOL)
            ->setParameter('lastsync', $lastSync)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string $countryCode
     * @return Mirror[]
     */
    private function findByCountry(string $countryCode): array
    {
        return $this->createQueryBuilder('mirror')
            ->where('mirror.protocol = :protocol')
            ->andWhere('mirror.country = :country')
            ->andWhere('mirror.active = true')
            ->andWhere('mirror.isos = true')
            ->orderBy('mirror.score')
            ->setParameter('protocol', self::PROTOCOL)
            ->setParameter('country', $countryCode)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Mirror[]
     */
    private function findActive(): array
    {
        return $this->findBy(
            [
                'protocol' => self::PROTOCOL,
                'active' => true,
                'isos' => true,
            ],
            ['score' => 'DESC']
        );
    }

    /**
     * @return Mirror[]
     */
    public function findSecure(): array
    {
        return $this->findBy(['protocol' => self::PROTOCOL], ['score' => 'DESC']);
    }

    /**
     * @param array $urls
     * @return Mirror[]
     */
    public function findAllExceptByUrls(array $urls): array
    {
        return $this
            ->createQueryBuilder('mirror')
            ->where('mirror.url NOT IN (:urls)')
            ->setParameter('urls', $urls)
            ->getQuery()
            ->getResult();
    }
}
