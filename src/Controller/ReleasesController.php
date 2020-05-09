<?php

namespace App\Controller;

use App\Entity\Release;
use App\Repository\ReleaseRepository;
use App\Request\PaginationRequest;
use App\Request\QueryRequest;
use App\SearchRepository\ReleaseSearchRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReleasesController extends AbstractController
{
    /** @var ReleaseRepository */
    private $releaseRepository;

    /** @var ReleaseSearchRepository */
    private $releaseSearchRepository;

    /**
     * @param ReleaseRepository $releaseRepository
     * @param ReleaseSearchRepository $releaseSearchRepository
     */
    public function __construct(ReleaseRepository $releaseRepository, ReleaseSearchRepository $releaseSearchRepository)
    {
        $this->releaseRepository = $releaseRepository;
        $this->releaseSearchRepository = $releaseSearchRepository;
    }

    /**
     * @Route("/releases/feed", methods={"GET"})
     * @Cache(maxage="300", smaxage="600")
     * @return Response
     */
    public function feedAction(): Response
    {
        $response = $this->render(
            'releases/feed.xml.twig',
            ['releases' => $this->releaseRepository->findAllAvailable()]
        );
        $response->headers->set('Content-Type', 'application/atom+xml; charset=UTF-8');
        return $response;
    }

    /**
     * @Route("/api/releases", methods={"GET"})
     * @Cache(maxage="300", smaxage="600")
     * @param QueryRequest $queryRequest
     * @param PaginationRequest $paginationRequest
     * @return Response
     */
    public function releasesAction(QueryRequest $queryRequest, PaginationRequest $paginationRequest): Response
    {
        return $this->json(
            $this->releaseSearchRepository->findAllByQuery(
                $paginationRequest->getOffset(),
                $paginationRequest->getLimit(),
                $queryRequest->getQuery()
            )
        );
    }

    /**
     * @Route("/api/releases/{version<^[0-9]+[\.\-\w]+$>}", methods={"GET"})
     * @Cache(maxage="300", smaxage="600")
     * @param Release $release
     * @return Response
     */
    public function apiReleaseAction(Release $release): Response
    {
        return $this->json($release);
    }
}
