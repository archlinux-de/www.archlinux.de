<?php

namespace App\Repository;

use App\Entity\Mirror;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

class MirrorRepository extends ServiceEntityRepository
{
    private const PROTOCOL = 'https';

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
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
     * @param int $offset
     * @param int $limit
     * @param string $query
     * @return Paginator<Mirror>
     */
    public function findSecureByQuery(int $offset, int $limit, string $query): Paginator
    {
        $queryBuilder = $this
            ->createQueryBuilder('mirror')
            ->where('mirror.protocol = :protocol')
            ->orderBy('mirror.score', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->setParameter('protocol', self::PROTOCOL);

        if ($query) {
            $queryBuilder
                ->andWhere('mirror.url LIKE :query')
                ->setParameter('query', '%' . $query . '%');
        }

        return new Paginator($queryBuilder);
    }

    /**
     * @param string[] $urls
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
