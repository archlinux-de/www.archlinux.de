<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class RecentNewsController extends Controller
{
    /**
     * @Route("/feed/news")
     * @return Response
     */
    public function indexAction(): Response
    {
        return $this->redirect($this->getParameter('app.news.feed'));
    }
}
