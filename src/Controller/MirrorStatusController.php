<?php

namespace App\Controller;

use App\Repository\MirrorRepository;
use DatatablesApiBundle\DatatablesResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MirrorStatusController extends AbstractController
{
    /**
     * @Route("/mirrors", methods={"GET"})
     * @Cache(smaxage="900")
     * @return Response
     */
    public function indexAction(): Response
    {
        return $this->render('mirrors/index.html.twig');
    }

    /**
     * @Route("/mirrors/datatables", methods={"GET"})
     * @param MirrorRepository $mirrorRepository
     * @return Response
     */
    public function datatablesAction(MirrorRepository $mirrorRepository): Response
    {
        $response = new DatatablesResponse($mirrorRepository->findSecure());

        $jsonResponse = $this->json($response);
        // Only cache the first draw
        if ($response->getDraw() == 1) {
            $jsonResponse->setMaxAge(300);
            $jsonResponse->setSharedMaxAge(3600);
        }
        return $jsonResponse;
    }
}
