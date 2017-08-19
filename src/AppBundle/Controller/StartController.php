<?php

namespace AppBundle\Controller;

use Doctrine\DBAL\Driver\Connection;
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
     * @Route("/")
     * @return Response
     */
    public function indexAction(): Response
    {
        $architectureId = $this->getArchitectureId($this->getParameter('app.packages.default_architecture'));

        return $this->render('start/index.html.twig', [
            'architecture_id' => $architectureId
        ]);
    }

    /**
     * @param string $architectureName
     *
     * @return int
     */
    private function getArchitectureId(string $architectureName): int
    {
        $stm = $this->database->prepare('
            SELECT
                id
            FROM
                architectures
            WHERE
                name = :architectureName
            ');
        $stm->bindParam('architectureName', $architectureName, \PDO::PARAM_STR);
        $stm->execute();

        return $stm->fetchColumn();
    }

    /**
     * @return Response
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
            'news_feed_url' => $this->getParameter('app.news.feed'),
            'news_archive_url' => $this->getParameter('app.news.archive')
        ]);
    }

    /**
     * @return Response
     */
    public function recentPackagesAction(): Response
    {
        $architectureId = $this->getArchitectureId($this->getParameter('app.packages.default_architecture'));

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
            AND repositories.arch = :architectureId
            AND architectures.id = repositories.arch
        ORDER BY
            packages.builddate DESC
        LIMIT
            20
        ');
        $packages->bindParam('architectureId', $architectureId, \PDO::PARAM_INT);
        $packages->execute();

        return $this->render('start/recent_packages.html.twig', [
            'packages' => $packages
        ]);
    }
}
