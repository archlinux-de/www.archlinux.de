<?php

namespace App\Controller;

use App\Entity\Mirror;
use App\Request\PaginationRequest;
use App\Request\QueryRequest;
use App\SearchRepository\MirrorSearchRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MirrorStatusController extends AbstractController
{
    /** @var MirrorSearchRepository */
    private $mirrorSearchRepository;

    /**
     * @param MirrorSearchRepository $mirrorSearchRepository
     */
    public function __construct(MirrorSearchRepository $mirrorSearchRepository)
    {
        $this->mirrorSearchRepository = $mirrorSearchRepository;
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
            $this->mirrorSearchRepository->findSecureByQuery(
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
