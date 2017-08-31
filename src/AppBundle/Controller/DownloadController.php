<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Mirror;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class DownloadController extends Controller
{
    /** @var Connection */
    private $database;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->database = $connection;
    }

    /**
     * @Route("/download", methods={"GET"})
     * @Cache(smaxage="600")
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function indexAction(EntityManagerInterface $entityManager): Response
    {
        $release = $this->database->query('
            SELECT
                version,
                kernel_version,
                SUBSTRING(iso_url, 2) AS file_path,
                md5_sum,
                sha1_sum,
                torrent_file_name AS file_name,
                torrent_file_length AS file_length,
                magnet_uri,
                created AS creation_time
            FROM
                releng_releases
            WHERE
                available = 1
            ORDER BY
                release_date DESC
            LIMIT 1
            ')->fetch();

        $mirrors = $entityManager->createQueryBuilder()
            ->select('mirror.url')
            ->from(Mirror::class, 'mirror')
            ->where('mirror.protocol = :protocol')
            ->andWhere('mirror.country = :country')
            ->andWhere('mirror.lastSync > :lastsync')
            ->orderBy('mirror.score')
            ->setParameter('protocol', 'https')
            ->setParameter('country', $this->getParameter('app.mirrors.country'))
            ->setParameter('lastsync', $release['creation_time'])
            ->getQuery()
            ->getResult();

        return $this->render('download/index.html.twig', [
            'release' => $release,
            'mirrors' => $mirrors
        ]);
    }
}
