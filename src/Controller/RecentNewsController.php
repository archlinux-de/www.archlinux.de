<?php

namespace App\Controller;

use App\Repository\NewsItemRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RecentNewsController extends AbstractController
{

    /**
     * @Route("/news/feed", methods={"GET"})
     * @Cache(smaxage="600")
     * @param NewsItemRepository $newsItemRepository
     * @return Response
     */
    public function indexAction(NewsItemRepository $newsItemRepository): Response
    {
        $response = $this->render(
            'news/feed.xml.twig',
            ['items' => $newsItemRepository->findLatest(25)]
        );
        $response->headers->set('Content-Type', 'application/atom+xml; charset=UTF-8');
        return $response;
    }
}
