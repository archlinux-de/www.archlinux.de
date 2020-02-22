<?php

namespace App\Controller;

use App\Datatables\DatatablesResponse;
use App\Entity\Mirror;
use App\Repository\MirrorRepository;
use App\Request\PaginationRequest;
use App\Request\QueryRequest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MirrorStatusController extends AbstractController
{
    /** @var MirrorRepository */
    private $mirrorRepository;

    /**
     * @param MirrorRepository $mirrorRepository
     */
    public function __construct(MirrorRepository $mirrorRepository)
    {
        $this->mirrorRepository = $mirrorRepository;
    }

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
     * @Cache(maxage="300", smaxage="3600")
     * @return Response
     */
    public function datatablesAction(): Response
    {
        return $this->json(new DatatablesResponse($this->mirrorRepository->findSecure()));
    }

    /**
     * @Route("/api/mirrors", methods={"GET"})
     * @Cache(maxage="300", smaxage="600")
     * @param QueryRequest $queryRequest
     * @param PaginationRequest $paginationRequest
     * @return Response
     */
    public function mirrorsAction(QueryRequest $queryRequest, PaginationRequest $paginationRequest): Response
    {
        return $this->json(
            $this->mirrorRepository->findSecureByQuery(
                $paginationRequest->getOffset(),
                $paginationRequest->getLimit(),
                $queryRequest->getQuery()
            )
        );
    }

    /**
     * @Route("/api/mirrors/{url<.+>}", methods={"GET"})
     * @Cache(maxage="300", smaxage="600")
     * @param Mirror $mirror
     * @return Response
     */
    public function mirrorAction(Mirror $mirror): Response
    {
        return $this->json($mirror);
    }
}
