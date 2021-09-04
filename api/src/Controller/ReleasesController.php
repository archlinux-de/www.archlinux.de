<?php

namespace App\Controller;

use App\Entity\Release;
use App\Repository\ReleaseRepository;
use App\Request\PaginationRequest;
use App\Request\QueryRequest;
use App\SearchRepository\ReleaseSearchRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReleasesController extends AbstractController
{
    public function __construct(
        private ReleaseRepository $releaseRepository,
        private ReleaseSearchRepository $releaseSearchRepository
    ) {
    }

    #[Route(path: '/releases/feed', methods: ['GET'])]
    #[Cache(maxage: 300, smaxage: 600)]
    public function feedAction(): Response
    {
        $response = $this->render(
            'releases/feed.xml.twig',
            ['releases' => $this->releaseRepository->findAllAvailable()]
        );
        $response->headers->set('Content-Type', 'application/atom+xml; charset=UTF-8');
        return $response;
    }

    #[Route(path: '/api/releases', methods: ['GET'])]
    #[Cache(maxage: 300, smaxage: 600)]
    public function releasesAction(
        QueryRequest $queryRequest,
        PaginationRequest $paginationRequest,
        Request $request
    ): Response {
        return $this->json(
            $this->releaseSearchRepository->findAllByQuery(
                $paginationRequest->getOffset(),
                $paginationRequest->getLimit(),
                $queryRequest->getQuery(),
                $request->get('onlyAvailable', 'false') == 'true'
            )
        );
    }

    #[Route(path: '/api/releases/{version<^[0-9]+[\.\-\w]+$>}', methods: ['GET'])]
    #[Cache(maxage: 300, smaxage: 600)]
    public function apiReleaseAction(Release $release): Response
    {
        return $this->json($release);
    }
}
