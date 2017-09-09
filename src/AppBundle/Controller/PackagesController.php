<?php

namespace AppBundle\Controller;

use Doctrine\DBAL\Query\QueryBuilder;
use Psr\Cache\CacheItemPoolInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Request\Datatables\Request as DatatablesRequest;
use AppBundle\Response\Datatables\Response as DatatablesResponse;

class PackagesController extends Controller
{
    /** @var CacheItemPoolInterface */
    private $cache;

    /**
     * @param CacheItemPoolInterface $cache
     */
    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

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
            'search' => $request->get('search'),
            'packager' => $request->get('packager')
        ]);
    }

    /**
     * @Route("/packages/datatables", methods={"GET"})
     * @param DatatablesRequest $request
     * @return Response
     */
    public function datatablesAction(DatatablesRequest $request): Response
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

        $cachedPackageCount = $this->cache->getItem('packages.count');
        if ($cachedPackageCount->isHit()) {
            /** @var int $packageCount */
            $packageCount = $cachedPackageCount->get();
        } else {
            $packageCount = $this->getDoctrine()->getConnection()->createQueryBuilder()
                ->select('COUNT(*)')->from('packages')->execute()->fetchColumn();
            $cachedPackageCount->expiresAt(new \DateTime('24 hour'));
            $cachedPackageCount->set($packageCount);
            $this->cache->save($cachedPackageCount);
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
     * @return DatatablesResponse
     */
    private function queryDatabase(DatatablesRequest $request): DatatablesResponse
    {
        $compareableColumns = [
            'repository' => 'repositories.name',
            'architecture' => 'architectures.name'
        ];
        $searchableColumns = array_merge(
            $compareableColumns,
            [
                'name' => 'packages.name',
                'description' => 'packages.desc',
                'packager' => 'packages.packager'
            ]
        );
        $orderableColumns = array_merge(
            $compareableColumns,
            [
                'builddate' => 'packages.builddate',
                'name' => 'packages.name'
            ]
        );

        $connection = $this->getDoctrine()->getConnection();
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder
            ->select([
                'SQL_CALC_FOUND_ROWS repositories.name AS repository',
                'architectures.name AS architecture',
                'packages.name AS name',
                'packages.version',
                'packages.desc AS description',
                'packages.builddate',
                'packages.packager'
            ])
            ->from('packages')
            ->from('repositories')
            ->from('architectures')
            ->where('packages.repository = repositories.id')
            ->andWhere('architectures.id = repositories.arch')
            ->setFirstResult($request->getStart())
            ->setMaxResults($request->getLength());

        foreach ($request->getOrders() as $order) {
            $orderColumnName = $order->getColumn()->getData();
            if (isset($orderableColumns[$orderColumnName])) {
                $queryBuilder->orderBy($orderColumnName, $order->getDir());
            }
        }

        if ($request->hasSearch() && !$request->getSearch()->isRegex()) {
            $queryBuilder->andWhere('(packages.name LIKE :search OR packages.desc LIKE :search)');
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

        $packages = $queryBuilder->execute()->fetchAll(\PDO::FETCH_ASSOC);

        array_walk($packages, function (&$package) {
            $package['url'] = $this->generateUrl(
                'app_packagedetails_index',
                [
                    'repo' => $package['repository'],
                    'arch' => $package['architecture'],
                    'pkgname' => $package['name']
                ]
            );
        });

        $packagesFiltered = $connection->createQueryBuilder()
            ->select('FOUND_ROWS()')->execute()->fetchColumn();

        $response = new DatatablesResponse($packages);
        $response->setRecordsFiltered($packagesFiltered);

        return $response;
    }
}
