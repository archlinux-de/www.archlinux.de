<?php

namespace AppBundle\Controller;

use Doctrine\DBAL\Query\QueryBuilder;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Request\Datatables\Request as DatatablesRequest;

class PackagesController extends Controller
{
    /**
     * @Route("/packages", methods={"GET"})
     * @return Response
     */
    public function indexAction(): Response
    {
        return $this->render('packages/index.html.twig', [
            'default_architecture' => $this->getParameter('app.packages.default_architecture'),
            'architectures' => $this->getAvailableArchitectures(),
            'repositories' => $this->getAvailableRepositories()
        ])->setSharedMaxAge(600);
    }

    /**
     * @Route("/packages/datatables", methods={"GET"})
     * @param DatatablesRequest $request
     * @return Response
     */
    public function datatablesAction(DatatablesRequest $request): Response
    {
        $compareableColumns = [
            'repository' => 'repositories.name',
            'architecture' => 'architectures.name'
        ];
        $searchableColumns = array_merge(
            $compareableColumns,
            [
                'name' => 'packages.name',
                'description' => 'packages.desc'
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
                'repositories.testing',
                'architectures.name AS architecture',
                'packages.name AS name',
                'packages.version',
                'packages.desc AS description',
                'packages.builddate'
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

        if (!$request->getSearch()->isRegex() && $request->getSearch()->isValid()) {
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
        $totalPackages = $connection->createQueryBuilder()
            ->select('COUNT(*)')->from('packages')->execute()->fetchColumn();

        return $this->json([
            'draw' => $request->getDraw(),
            'recordsTotal' => $totalPackages,
            'recordsFiltered' => $packagesFiltered,
            'data' => $packages
        ])->setSharedMaxAge(600);
    }

    /**
     * @return array
     */
    private function getAvailableRepositories(): array
    {
        return array_keys($this->getParameter('app.packages.repositories'));
    }

    /**
     * @return array
     */
    private function getAvailableArchitectures(): array
    {
        $uniqueArchitectures = array();
        foreach ($this->getParameter('app.packages.repositories') as $architectures) {
            foreach ($architectures as $architecture) {
                $uniqueArchitectures[$architecture] = 1;
            }
        }

        return array_keys($uniqueArchitectures);
    }
}
