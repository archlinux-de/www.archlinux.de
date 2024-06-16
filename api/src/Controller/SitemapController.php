<?php

namespace App\Controller;

use App\Repository\NewsItemRepository;
use App\Repository\PackageRepository;
use App\Repository\ReleaseRepository;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SitemapController extends AbstractController
{
    public function __construct(private readonly string $defaultArchitecture)
    {
    }

    #[Route(path: '/sitemap.xml', methods: ['GET'])]
    #[Cache(smaxage: 600)]
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
