<?php

namespace App\Controller;

use App\Entity\NewsItem;
use App\Repository\NewsItemRepository;
use App\Request\PaginationRequest;
use App\Request\QueryRequest;
use App\SearchRepository\NewsItemSearchRepository;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NewsController extends AbstractController
{
    public function __construct(
        private readonly NewsItemRepository $newsRepository,
        private readonly NewsItemSearchRepository $newsItemSearchRepository
    ) {
    }

    #[Route(path: '/api/news', methods: ['GET'])]
    #[Cache(maxage: 300, smaxage: 600)]
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

    #[Route(path: '/api/news/{id<\d+>}', methods: ['GET'])]
    #[Cache(maxage: 300, smaxage: 600)]
    public function newsItemAction(NewsItem $newsItem): Response
    {
        return $this->json($newsItem);
    }

    #[Route(path: '/news/feed', methods: ['GET'])]
    #[Cache(maxage: 300, smaxage: 600)]
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
