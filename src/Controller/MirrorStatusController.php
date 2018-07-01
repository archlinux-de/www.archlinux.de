<?php

namespace App\Controller;

use App\Repository\MirrorRepository;
use App\Response\Datatables\Response as DatatablesResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class MirrorStatusController extends Controller
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
     * @Cache(smaxage="600")
     * @param MirrorRepository $mirrorRepository
     * @return Response
     */
    public function datatablesAction(MirrorRepository $mirrorRepository): Response
    {
        return $this->json(new DatatablesResponse($mirrorRepository->findSecure()));
    }
}
