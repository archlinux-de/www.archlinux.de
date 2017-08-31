<?php

namespace AppBundle\Controller;

use AppBundle\Entity\NewsItem;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class StartController extends Controller
{
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
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @Cache(smaxage="600")
     */
    public function newsAction(EntityManagerInterface $entityManager): Response
    {
        /** @var NewsItem[] $newsItems */
        $newsItems = $entityManager
            ->createQueryBuilder()
            ->select('news')
            ->from('AppBundle:NewsItem', 'news')
            ->orderBy('news.lastModified', 'DESC')
            ->setMaxResults(6)
            ->getQuery()
            ->getResult();

        return $this->render('start/news.html.twig', [
            'news_items' => $newsItems,
            'news_archive_url' => $this->getParameter('app.news.archive')
        ]);
    }

    /**
     * @param Connection $connection
     * @return Response
     * @Cache(smaxage="600")
     */
    public function recentPackagesAction(Connection $connection): Response
    {
        $packages = $connection->prepare('
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
