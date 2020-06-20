<?php

namespace App\Controller;

use App\Repository\NewsItemRepository;
use App\Repository\PackageRepository;
use App\Repository\ReleaseRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SitemapController extends AbstractController
{
    /**
     * @Route("/sitemap.xml", methods={"GET"})
     * @Cache(smaxage="600")
     * @param PackageRepository $packageRepository
     * @param NewsItemRepository $newsItemRepository
     * @param ReleaseRepository $releaseRepository
     * @param string $defaultArchitecture
     * @return Response
     */
    public function indexAction(
        PackageRepository $packageRepository,
        NewsItemRepository $newsItemRepository,
        ReleaseRepository $releaseRepository,
        string $defaultArchitecture
    ): Response {
        $packages = $packageRepository->findStableByArchitecture($defaultArchitecture);

        $response = $this->render(
            'sitemap/index.xml.twig',
            [
                'packages' => $packages,
                'news' => $newsItemRepository->findAll(),
                'releases' => $releaseRepository->findAll()
            ]
        );
        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');
        return $response;
    }
}
