<?php

namespace App\Controller;

use App\Entity\Mirror;
use App\Request\PaginationRequest;
use App\Request\QueryRequest;
use App\SearchRepository\MirrorSearchRepository;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MirrorStatusController extends AbstractController
{
    public function __construct(private MirrorSearchRepository $mirrorSearchRepository)
    {
    }

    #[Route(path: '/api/mirrors', methods: ['GET'])]
    #[Cache(maxage: 300, smaxage: 600)]
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

    #[Route(path: '/api/mirrors/{url<.+>}', methods: ['GET'])]
    #[Cache(maxage: 300, smaxage: 600)]
    public function mirrorAction(Mirror $mirror): Response
    {
        return $this->json($mirror);
    }
}
