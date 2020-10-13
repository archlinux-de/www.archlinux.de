<?php

namespace App\Controller;

use App\Entity\NewsItem;
use App\Repository\NewsItemRepository;
use App\Request\PaginationRequest;
use App\Request\QueryRequest;
use App\SearchRepository\NewsItemSearchRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class NewsController extends AbstractController
{
    /** @var NewsItemRepository */
    private $newsRepository;

    /** @var NewsItemSearchRepository */
    private $newsItemSearchRepository;

    /**
     * @param NewsItemRepository $newsRepository
     * @param NewsItemSearchRepository $newsItemSearchRepository
     */
    public function __construct(
        NewsItemRepository $newsRepository,
        NewsItemSearchRepository $newsItemSearchRepository
    ) {
        $this->newsRepository = $newsRepository;
        $this->newsItemSearchRepository = $newsItemSearchRepository;
    }

    /**
     * @Route("/api/news", methods={"GET"})
     * @Cache(maxage="300", smaxage="600")
     * @param QueryRequest $queryRequest
     * @param PaginationRequest $paginationRequest
     * @return Response
     */
    public function newsAction(QueryRequest $queryRequest, PaginationRequest $paginationRequest): Response
    {
        return $this->json(
            $this->newsItemSearchRepository->findLatestByQuery(
                $paginationRequest->getOffset(),
                $paginationRequest->getLimit(),
                $queryRequest->getQuery()
            )
        );
    }

    /**
     * @Route("/api/news/{id<\d+>}", methods={"GET"})
     * @Cache(maxage="300", smaxage="600")
     * @param NewsItem $newsItem
     * @return Response
     */
    public function newsItemAction(NewsItem $newsItem): Response
    {
        return $this->json($newsItem);
    }

    /**
     * @Route("/news/feed", methods={"GET"})
     * @Cache(maxage="300", smaxage="600")
     * @return Response
     */
    public function feedAction(): Response
    {
        $response = $this->render(
            'news/feed.xml.twig',
            ['items' => $this->newsRepository->findLatest(25)]
        );
        $response->headers->set('Content-Type', 'application/atom+xml; charset=UTF-8');
        return $response;
    }
}
