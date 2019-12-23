<?php

namespace App\Controller;

use App\Entity\NewsItem;
use App\Repository\NewsItemRepository;
use App\Datatables\DatatablesColumnConfiguration;
use App\Datatables\DatatablesQuery;
use App\Datatables\DatatablesRequest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NewsController extends AbstractController
{
    /** @var NewsItemRepository */
    private $newsRepository;

    /** @var DatatablesQuery */
    private $datatablesQuery;

    /**
     * @param NewsItemRepository $newsRepository
     * @param DatatablesQuery $datatablesQuery
     */
    public function __construct(NewsItemRepository $newsRepository, DatatablesQuery $datatablesQuery)
    {
        $this->newsRepository = $newsRepository;
        $this->datatablesQuery = $datatablesQuery;
    }

    /**
     * @Route("/news", methods={"GET"})
     * @Cache(smaxage="900")
     * @param Request $request
     * @return Response
     */
    public function indexAction(Request $request): Response
    {
        $search = $request->get('search');
        return $this->render(
            'news/index.html.twig',
            ['search' => $search]
        );
    }

    /**
     * @Route("/news/datatables", methods={"GET"})
     * @param DatatablesRequest $request
     * @return Response
     */
    public function datatablesAction(DatatablesRequest $request): Response
    {
        $columnConfiguration = (new DatatablesColumnConfiguration())
            ->addTextSearchableColumn('title', 'news.title')
            ->addTextSearchableColumn('description', 'news.description')
            ->addTextSearchableColumn('author.name', 'news.author.name')
            ->addOrderableColumn('lastModified', 'news.lastModified');
        $response = $this->datatablesQuery->getResult(
            $request,
            $columnConfiguration,
            $this->newsRepository
                ->createQueryBuilder('news'),
            $this->newsRepository->getSize()
        );

        $jsonResponse = $this->json($response);
        // Only cache the first draw
        if ($response->getDraw() == 1) {
            $jsonResponse->setMaxAge(300);
            $jsonResponse->setSharedMaxAge(3600);
        }
        return $jsonResponse;
    }

    /**
     * @Route("/news/{slug}", methods={"GET"}, requirements={"slug": "^[0-9]+-[a-z0-9_\-\.]+$"})
     * @Cache(smaxage="900")
     * @param NewsItem $newsItem
     * @return Response
     */
    public function itemAction(NewsItem $newsItem): Response
    {
        return $this->render(
            'news/item.html.twig',
            ['news' => $newsItem]
        );
    }

    /**
     * @Route("/news/feed", methods={"GET"})
     * @Cache(smaxage="600")
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
