<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class RecentNewsController extends Controller
{
    /**
     * @Route("/news/feed", methods={"GET"})
     * @return Response
     */
    public function indexAction(): Response
    {
        return $this->redirect($this->getParameter('app.news.feed'))->setSharedMaxAge(600);
    }
}
