<?php

namespace App\Controller\Statistics;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class StatisticsController extends Controller
{
    /**
     * @Route("/statistics", methods={"GET"})
     * @Cache(smaxage="900")
     * @return Response
     */
    public function indexAction(): Response
    {
        return $this->render('statistics/index.html.twig');
    }
}
