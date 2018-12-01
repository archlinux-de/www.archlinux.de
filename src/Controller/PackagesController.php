<?php

namespace App\Controller;

use App\Repository\PackageRepository;
use App\Request\Datatables\Column;
use App\Request\Datatables\Order;
use App\Request\Datatables\Request as DatatablesRequest;
use App\Request\Datatables\Search;
use App\Response\Datatables\Response as DatatablesResponse;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Psr\Cache\CacheItemPoolInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PackagesController extends AbstractController
{
    /** @var CacheItemPoolInterface */
    private $cache;

    /** @var PackageRepository */
    private $packageRepository;

    /**
     * @param CacheItemPoolInterface $cache
     * @param PackageRepository $packageRepository
     */
    public function __construct(CacheItemPoolInterface $cache, PackageRepository $packageRepository)
    {
        $this->cache = $cache;
        $this->packageRepository = $packageRepository;
    }

    /**
     * @Route("/packages", methods={"GET"})
     * @Cache(smaxage="600")
     * @param Request $request
     * @return Response
     */
    public function indexAction(Request $request): Response
    {
        $search = $request->get('search');
        $defaultArchitecture = $this->getParameter('app.packages.default_architecture');
        $architecture = $request->get('architecture', $defaultArchitecture);
        $repository = $request->get('repository');

        $datatablesRequest = new DatatablesRequest(0, 0, 25);
        $datatablesRequest->addOrder(
            new Order(
                new Column(
                    6,
                    'builddate',
                    '',
                    false,
                    true,
                    new Search('', false)
                ),
                'desc'
            )
        );
        if (!is_null($search)) {
            $datatablesRequest->setSearch(new Search($search, false));
        }
        $datatablesRequest->addColumn(
            new Column(
                2,
                'architecture',
                '',
                true,
                true,
                new Search(
                    $architecture,
                    false
                )
            )
        );
        if (!is_null($repository)) {
            $datatablesRequest->addColumn(
                new Column(
                    0,
                    'repository.name',
                    '',
                    true,
                    true,
                    new Search(
                        $repository,
                        false
                    )
                )
            );
        }
        $datatablesResponse = $this->createDatatablesResponse($datatablesRequest);

        return $this->render('packages/index.html.twig', [
            'architecture' => $architecture,
            'defaultArchitecture' => $defaultArchitecture,
            'repository' => $repository,
            'search' => $search,
            'datatablesResponse' => $datatablesResponse
        ]);
    }

    /**
     * @param DatatablesRequest $request
     * @return DatatablesResponse
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function createDatatablesResponse(DatatablesRequest $request): DatatablesResponse
    {
        $cachedResponse = $this->cache->getItem($request->getId());
        if ($cachedResponse->isHit()) {
            /** @var DatatablesResponse $response */
            $response = $cachedResponse->get();
        } else {
            $response = $this->queryDatabase($request);
            $cachedResponse->expiresAt(new \DateTime('1 hour'));
            $cachedResponse->set($response);
            // Only store the first draw (initial state of the page)
            if ($request->getDraw() == 1) {
                $this->cache->save($cachedResponse);
            }
        }

        $packageCount = $this->calculatePackageCount();

        $response->setRecordsTotal($packageCount);
        $response->setDraw($request->getDraw());
        return $response;
    }

    /**
     * @param DatatablesRequest $request
     * @return DatatablesResponse
     */
    private function queryDatabase(DatatablesRequest $request): DatatablesResponse
    {
        $compareableColumns = [
            'repository.name' => 'repository.name',
            'architecture' => 'repository.architecture'
        ];
        $textSearchableColumns = [
            'name' => 'package.name',
            'description' => 'package.description',
            'groups' => 'package.groups'
        ];
        $searchableColumns = array_merge(
            $compareableColumns,
            $textSearchableColumns
        );
        $orderableColumns = array_merge(
            $compareableColumns,
            [
                'builddate' => 'package.buildDate',
                'name' => 'package.name'
            ]
        );

        $queryBuilder = $this->packageRepository
            ->createQueryBuilder('package')
            ->addSelect('repository')
            ->join('package.repository', 'repository')
            ->setFirstResult($request->getStart())
            ->setMaxResults($request->getLength());

        foreach ($request->getOrders() as $order) {
            $orderColumnName = $order->getColumn()->getData();
            if (isset($orderableColumns[$orderColumnName])) {
                $queryBuilder->orderBy($orderableColumns[$orderColumnName], $order->getDir());
            }
        }

        if ($request->hasSearch() && !$request->getSearch()->isRegex()) {
            $queryBuilder->andWhere($this->createTextSearchQuery($textSearchableColumns));
            $queryBuilder->setParameter(':search', '%' . $request->getSearch()->getValue() . '%');
        }

        foreach ($request->getColumns() as $column) {
            if ($column->isSearchable()) {
                $columnName = $column->getData();
                if (isset($searchableColumns[$columnName])) {
                    if (!$column->getSearch()->isRegex() && $column->getSearch()->isValid()) {
                        $queryBuilder->andWhere(
                            $searchableColumns[$columnName] . ' LIKE :columnSearch' . $column->getId()
                        );
                        $searchValue = $column->getSearch()->getValue();
                        if (!isset($compareableColumns[$columnName])) {
                            $searchValue = '%' . $searchValue . '%';
                        }
                        $queryBuilder->setParameter(':columnSearch' . $column->getId(), $searchValue);
                    }
                }
            }
        }

        $pagination = new Paginator($queryBuilder, false);
        $packagesFiltered = $pagination->count();
        $packages = $pagination->getQuery()->getResult();

        $response = new DatatablesResponse($packages);
        $response->setRecordsFiltered($packagesFiltered);

        return $response;
    }

    /**
     * @param $textSearchableColumns
     * @return string
     */
    private function createTextSearchQuery($textSearchableColumns): string
    {
        $textSearchesArray = iterator_to_array($this->createTextSearchesIterator($textSearchableColumns));
        return '(' . implode(' OR ', $textSearchesArray) . ')';
    }

    /**
     * @param $textSearchableColumns
     * @return \Iterator
     */
    private function createTextSearchesIterator($textSearchableColumns): \Iterator
    {
        foreach ($textSearchableColumns as $textSearchableColumn) {
            yield $textSearchableColumn . ' LIKE :search';
        }
    }

    /**
     * @return int
     */
    private function calculatePackageCount(): int
    {
        $cachedPackageCount = $this->cache->getItem('packages.count');
        if ($cachedPackageCount->isHit()) {
            /** @var int $packageCount */
            $packageCount = $cachedPackageCount->get();
        } else {
            $packageCount = $this->packageRepository->getSize();

            $cachedPackageCount->expiresAt(new \DateTime('1 hour'));
            $cachedPackageCount->set($packageCount);
            $this->cache->save($cachedPackageCount);
        }
        return $packageCount;
    }

    /**
     * @Route("/packages/datatables", methods={"GET"})
     * @param DatatablesRequest $request
     * @return Response
     */
    public function datatablesAction(DatatablesRequest $request): Response
    {
        $response = $this->createDatatablesResponse($request);

        return $this->json($response);
    }
}
