<?php

namespace AppBundle\Controller;

use Doctrine\DBAL\Driver\Connection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class StartController extends Controller
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
     * @Route("/", methods={"GET"})
     * @Cache(smaxage="600")
     * @return Response
     */
    public function indexAction(): Response
    {
        return $this->render('start/index.html.twig');
    }

    /**
     * @return Response
     * @Cache(smaxage="600")
     */
    public function newsAction(): Response
    {
        $newsFeed = $this->database->query('
            SELECT
                link,
                title,
                updated,
                summary
            FROM
                news_feed
            ORDER BY
                updated DESC
            LIMIT 6
            ');

        return $this->render('start/news.html.twig', [
            'news_feed' => $newsFeed,
            'news_archive_url' => $this->getParameter('app.news.archive')
        ]);
    }

    /**
     * @return Response
     * @Cache(smaxage="600")
     */
    public function recentPackagesAction(): Response
    {
        $packages = $this->database->prepare('
        SELECT
            packages.name,
            packages.version,
            repositories.name AS repository,
            repositories.testing,
            architectures.name AS architecture
        FROM
            packages,
            repositories,
            architectures
        WHERE
            packages.repository = repositories.id
            AND architectures.id = repositories.arch
            AND architectures.name = :architecture
        ORDER BY
            packages.builddate DESC
        LIMIT
            20
        ');
        $packages->bindValue('architecture', $this->getParameter('app.packages.default_architecture'), \PDO::PARAM_STR);
        $packages->execute();

        return $this->render('start/recent_packages.html.twig', [
            'packages' => $packages
        ]);
    }
}
