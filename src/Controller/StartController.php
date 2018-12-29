<?php

namespace App\Controller;

use App\Repository\NewsItemRepository;
use App\Repository\PackageRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StartController extends AbstractController
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
     * @param NewsItemRepository $newsItemRepository
     * @return Response
     * @Cache(smaxage="600")
     */
    public function newsAction(NewsItemRepository $newsItemRepository): Response
    {
        return $this->render('start/news.html.twig', [
            'news_items' => $newsItemRepository->findLatest(6)
        ]);
    }

    /**
     * @param PackageRepository $packageRepository
     * @return Response
     * @Cache(smaxage="600")
     */
    public function recentPackagesAction(PackageRepository $packageRepository): Response
    {
        $packages = $packageRepository->findLatestByArchitecture(
            $this->getParameter('app.packages.default_architecture'),
            20
        );

        return $this->render('start/recent_packages.html.twig', [
            'packages' => $packages
        ]);
    }
}
