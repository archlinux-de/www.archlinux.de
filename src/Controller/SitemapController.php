<?php

namespace App\Controller;

use App\Repository\PackageRepository;
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
     * @return Response
     */
    public function indexAction(PackageRepository $packageRepository): Response
    {
        $packages = $packageRepository->findStableByArchitecture(
            $this->getParameter('app.packages.default_architecture')
        );

        $response = $this->render(
            'sitemap/index.xml.twig',
            ['packages' => $packages]
        );
        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');
        return $response;
    }
}
