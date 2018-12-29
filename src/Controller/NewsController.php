<?php

namespace App\Controller;

use App\Repository\NewsItemRepository;
use DatatablesApiBundle\DatatablesColumnConfiguration;
use DatatablesApiBundle\DatatablesQuery;
use DatatablesApiBundle\DatatablesRequest;
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

        return $this->json($response);
    }
}
