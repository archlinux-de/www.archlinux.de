<?php

namespace AppBundle\Controller;

use archportal\lib\Config;
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
        $this->get('AppBundle\Service\LegacyEnvironment')->initialize();

        return $this->redirect(Config::get('news', 'feed'));
    }
}
