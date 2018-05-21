<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class LegalController extends Controller
{
    /**
     * @Route("/impressum", methods={"GET"})
     * @Cache(smaxage="900")
     * @return Response
     */
    public function impressumAction(): Response
    {
        return $this->render('legal/impressum.html.twig');
    }

    /**
     * @Route("/privacy-policy", methods={"GET"})
     * @Cache(smaxage="900")
     * @return Response
     */
    public function privacyPolicyAction(): Response
    {
        return $this->render('legal/privacy_policy.html.twig');
    }
}
