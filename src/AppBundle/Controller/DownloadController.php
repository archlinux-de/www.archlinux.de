<?php

namespace AppBundle\Controller;

use Doctrine\DBAL\Driver\Connection;
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
     * @return Response
     */
    public function indexAction(): Response
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

        $stm = $this->database->prepare('
            SELECT
                url
            FROM
                mirrors
            WHERE
                protocol = "https"
                AND countryCode = :country
                AND lastsync > :lastsync
            ORDER BY
                score ASC
            ');
        $stm->bindValue('country', $this->getParameter('app.mirrors.country'), \PDO::PARAM_STR);
        $stm->bindParam('lastsync', $release['creation_time'], \PDO::PARAM_INT);
        $stm->execute();
        $mirrors = $stm->fetchAll(\PDO::FETCH_COLUMN);

        return $this->render('download/index.html.twig', [
            'release' => $release,
            'mirrors' => $mirrors
        ]);
    }
}
