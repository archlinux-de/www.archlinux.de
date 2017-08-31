<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Mirror;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Response\Datatables\Response as DatatablesResponse;

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
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function datatablesAction(EntityManagerInterface $entityManager): Response
    {
        $mirrors = $entityManager->getRepository(Mirror::class)->findBy(['protocol' => 'https']);
        return $this->json(new DatatablesResponse($mirrors));
    }
}
