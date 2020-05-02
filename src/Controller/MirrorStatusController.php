<?php

namespace App\Controller;

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
