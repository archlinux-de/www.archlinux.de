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
    /** @var string */
    private $defaultArchitecture;

    /**
     * @param string $defaultArchitecture
     */
    public function __construct(string $defaultArchitecture)
    {
        $this->defaultArchitecture = $defaultArchitecture;
    }

    /**
     * @Route("/sitemap.xml", methods={"GET"})
     * @Cache(smaxage="600")
     * @param PackageRepository $packageRepository
     * @param NewsItemRepository $newsItemRepository
     * @param ReleaseRepository $releaseRepository
     * @return Response
     */
    public function indexAction(
        PackageRepository $packageRepository,
        NewsItemRepository $newsItemRepository,
        ReleaseRepository $releaseRepository
    ): Response {
        $packages = $packageRepository->findStableByArchitecture($this->defaultArchitecture);

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
