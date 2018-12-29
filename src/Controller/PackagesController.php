<?php

namespace App\Controller;

use App\Repository\PackageRepository;
use DatatablesApiBundle\DatatablesColumnConfiguration;
use DatatablesApiBundle\DatatablesQuery;
use DatatablesApiBundle\DatatablesRequest;
use DatatablesApiBundle\DatatablesResponse;
use DatatablesApiBundle\Request\Column;
use DatatablesApiBundle\Request\Order;
use DatatablesApiBundle\Request\Search;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PackagesController extends AbstractController
{
    /** @var PackageRepository */
    private $packageRepository;

    /** @var DatatablesQuery */
    private $datatablesQuery;

    /**
     * @param PackageRepository $packageRepository
     * @param DatatablesQuery $datatablesQuery
     */
    public function __construct(PackageRepository $packageRepository, DatatablesQuery $datatablesQuery)
    {
        $this->packageRepository = $packageRepository;
        $this->datatablesQuery = $datatablesQuery;
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

        return $this->render('packages/index.html.twig', [
            'architecture' => $architecture,
            'defaultArchitecture' => $defaultArchitecture,
            'repository' => $repository,
            'search' => $search,
            'datatablesResponse' => $this->createDatatablesResponse(
                $this->createInitialDatatablesRequest($search, $architecture, $repository)
            )
        ]);
    }

    /**
     * @param DatatablesRequest $request
     * @return DatatablesResponse
     */
    private function createDatatablesResponse(DatatablesRequest $request): DatatablesResponse
    {
        $columnConfiguration = (new DatatablesColumnConfiguration())
            ->addCompareableColumn('repository.name', 'repository.name')
            ->addCompareableColumn('architecture', 'repository.architecture')
            ->addTextSearchableColumn('name', 'package.name')
            ->addTextSearchableColumn('description', 'package.description')
            ->addTextSearchableColumn('groups', 'package.groups')
            ->addOrderableColumn('builddate', 'package.buildDate')
            ->addOrderableColumn('name', 'package.name');
        return $this->datatablesQuery->getResult(
            $request,
            $columnConfiguration,
            $this->packageRepository
                ->createQueryBuilder('package')
                ->addSelect('repository')
                ->join('package.repository', 'repository'),
            $this->packageRepository->getSize()
        );
    }

    /**
     * @param string|null $search
     * @param string $architecture
     * @param string|null $repository
     * @return DatatablesRequest
     */
    private function createInitialDatatablesRequest(
        ?string $search,
        string $architecture,
        ?string $repository
    ): DatatablesRequest {
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
        return $datatablesRequest;
    }

    /**
     * @Route("/packages/datatables", methods={"GET"})
     * @param DatatablesRequest $request
     * @return Response
     */
    public function datatablesAction(DatatablesRequest $request): Response
    {
        return $this->json($this->createDatatablesResponse($request));
    }
}
