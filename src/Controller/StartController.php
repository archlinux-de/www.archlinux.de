<?php

namespace App\Controller;

use App\Entity\NewsItem;
use App\Entity\Packages\Package;
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
            ->from(NewsItem::class, 'news')
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
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @Cache(smaxage="600")
     */
    public function recentPackagesAction(EntityManagerInterface $entityManager): Response
    {
        $packages = $entityManager
            ->createQueryBuilder()
            ->select('package', 'repository')
            ->from(Package::class, 'package')
            ->join('package.repository', 'repository', 'WITH', 'repository.architecture = :architecture')
            ->orderBy('package.buildDate', 'DESC')
            ->setMaxResults(20)
            ->setParameter('architecture', $this->getParameter('app.packages.default_architecture'))
            ->getQuery()
            ->getResult();

        return $this->render('start/recent_packages.html.twig', [
            'packages' => $packages
        ]);
    }
}
