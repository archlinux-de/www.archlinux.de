<?php

namespace App\Controller;

use App\Repository\PackageRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RecentPackagesController extends AbstractController
{

    /**
     * @Route("/packages/feed", methods={"GET"})
     * @Cache(smaxage="600")
     * @param PackageRepository $packageRepository
     * @return Response
     */
    public function indexAction(PackageRepository $packageRepository): Response
    {
        $packages = $packageRepository->findLatestByArchitecture(
            $this->getParameter('app.packages.default_architecture'),
            25
        );

        $response = $this->render(
            'packages/feed.xml.twig',
            ['packages' => $packages]
        );
        $response->headers->set('Content-Type', 'application/atom+xml; charset=UTF-8');
        return $response;
    }
}
