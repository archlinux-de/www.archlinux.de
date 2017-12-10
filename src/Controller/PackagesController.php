<?php

namespace App\Controller;

use App\Repository\PackageRepository;
use App\Request\Datatables\Request as DatatablesRequest;
use App\Response\Datatables\Response as DatatablesResponse;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Psr\Cache\CacheItemPoolInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PackagesController extends Controller
{
    /**
     * @Route("/packages", methods={"GET"})
     * @Cache(smaxage="600")
     * @param Request $request
     * @return Response
     */
    public function indexAction(Request $request): Response
    {
        return $this->render('packages/index.html.twig', [
            'architecture' => $request->get('architecture', $this->getParameter('app.packages.default_architecture')),
            'repository' => $request->get('repository'),
            'search' => $request->get('search')
        ]);
    }

    /**
     * @Route("/packages/datatables", methods={"GET"})
     * @param DatatablesRequest $request
     * @param CacheItemPoolInterface $cache
     * @param PackageRepository $packageRepository
     * @return Response
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function datatablesAction(
        DatatablesRequest $request,
        CacheItemPoolInterface $cache,
        PackageRepository $packageRepository
    ): Response {
        $cachedResponse = $cache->getItem($request->getId());
        if ($cachedResponse->isHit()) {
            /** @var DatatablesResponse $response */
            $response = $cachedResponse->get();
        } else {
            $response = $this->queryDatabase($request, $packageRepository);
            $cachedResponse->expiresAt(new \DateTime('1 hour'));
            $cachedResponse->set($response);
            // Only store the first draw (initial state of the page)
            if ($request->getDraw() == 1) {
                $cache->save($cachedResponse);
            }
        }

        $cachedPackageCount = $cache->getItem('packages.count');
        if ($cachedPackageCount->isHit()) {
            /** @var int $packageCount */
            $packageCount = $cachedPackageCount->get();
        } else {
            $packageCount = $packageRepository->getSize();

            $cachedPackageCount->expiresAt(new \DateTime('24 hour'));
            $cachedPackageCount->set($packageCount);
            $cache->save($cachedPackageCount);
        }

        $response->setRecordsTotal($packageCount);
        $response->setDraw($request->getDraw());

        return $this->json(
            $response,
            Response::HTTP_OK,
            [
                'X-Cache-App' => $cachedResponse->isHit() ? 'HIT' : 'MISS'
            ]
        );
    }

    /**
     * @param DatatablesRequest $request
     * @param PackageRepository $packageRepository
     * @return DatatablesResponse
     */
    private function queryDatabase(DatatablesRequest $request, PackageRepository $packageRepository): DatatablesResponse
    {
        $compareableColumns = [
            'repository.name' => 'repository.name',
            'architecture' => 'repository.architecture'
        ];
        $searchableColumns = array_merge(
            $compareableColumns,
            [
                'name' => 'package.name',
                'description' => 'package.description'
            ]
        );
        $orderableColumns = array_merge(
            $compareableColumns,
            [
                'builddate' => 'package.buildDate',
                'name' => 'package.name'
            ]
        );

        $queryBuilder = $packageRepository
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
            $queryBuilder->andWhere('(package.name LIKE :search OR package.description LIKE :search)');
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
}
