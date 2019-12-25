<?php

namespace App\Controller;

use App\Datatables\DatatablesColumnConfiguration;
use App\Datatables\DatatablesQuery;
use App\Datatables\DatatablesRequest;
use App\Entity\NewsItem;
use App\Repository\NewsItemRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class NewsController extends AbstractController
{
    /** @var NewsItemRepository */
    private $newsRepository;

    /** @var DatatablesQuery */
    private $datatablesQuery;

    /** @var SluggerInterface */
    private $slugger;

    /**
     * @param NewsItemRepository $newsRepository
     * @param DatatablesQuery $datatablesQuery
     * @param SluggerInterface $slugger
     */
    public function __construct(
        NewsItemRepository $newsRepository,
        DatatablesQuery $datatablesQuery,
        SluggerInterface $slugger
    ) {
        $this->newsRepository = $newsRepository;
        $this->datatablesQuery = $datatablesQuery;
        $this->slugger = $slugger;
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
     * @Route("/news/{id<[0-9]+>}-{slug<[\w\-\.]+>}", methods={"GET"})
     * @Cache(smaxage="900")
     * @param NewsItem $newsItem
     * @param string $slug
     * @return Response
     */
    public function itemAction(NewsItem $newsItem, string $slug): Response
    {
        $newsItemSlug = $this->slugger->slug($newsItem->getTitle());
        if ($slug != $newsItemSlug) {
            return $this->redirectToRoute(
                'app_news_item',
                ['id' => $newsItem->getId(), 'slug' => $newsItemSlug],
                Response::HTTP_MOVED_PERMANENTLY
            );
        }
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
