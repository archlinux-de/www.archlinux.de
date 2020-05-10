<?php

namespace App\Controller;

use App\Entity\Packages\Architecture;
use App\Entity\Packages\Package;
use App\Repository\PackageRepository;
use App\Request\PaginationRequest;
use App\Request\QueryRequest;
use App\SearchRepository\PackageSearchRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PackagesController extends AbstractController
{
    /** @var PackageRepository */
    private $packageRepository;

    /** @var string */
    private $defaultArchitecture;

    /** @var PackageSearchRepository */
    private $packageSearchRepository;

    /**
     * @param PackageRepository $packageRepository
     * @param string $defaultArchitecture
     * @param PackageSearchRepository $packageSearchRepository
     */
    public function __construct(
        PackageRepository $packageRepository,
        PackageSearchRepository $packageSearchRepository,
        string $defaultArchitecture
    ) {
        $this->packageRepository = $packageRepository;
        $this->packageSearchRepository = $packageSearchRepository;
        $this->defaultArchitecture = $defaultArchitecture;
    }

    /**
     * @Route("/packages/opensearch", methods={"GET"})
     * @Cache(maxage="300", smaxage="600")
     * @return Response
     */
    public function openSearchAction(): Response
    {
        $response = $this->render('packages/opensearch.xml.twig');
        $response->headers->set('Content-Type', 'application/opensearchdescription+xml; charset=UTF-8');
        return $response;
    }

    /**
     * @Route("/packages/feed", methods={"GET"})
     * @Cache(maxage="300", smaxage="600")
     * @param string $defaultArchitecture
     * @return Response
     */
    public function feedAction(string $defaultArchitecture): Response
    {
        $packages = $this->packageRepository->findLatestByArchitecture($defaultArchitecture, 25);

        $response = $this->render(
            'packages/feed.xml.twig',
            ['packages' => $packages]
        );
        $response->headers->set('Content-Type', 'application/atom+xml; charset=UTF-8');
        return $response;
    }

    /**
     * @Route("/packages/suggest", methods={"GET"})
     * @Cache(maxage="300", smaxage="600")
     * @param Request $request
     * @return Response
     */
    public function suggestAction(Request $request): Response
    {
        $term = $request->get('term');
        if (strlen($term) < 1 || strlen($term) > 50) {
            return $this->json([]);
        }
        $suggestions = $this->packageSearchRepository->findByTerm($term, 10);

        return $this->json(array_map(fn(Package $package): string => $package->getName(), $suggestions));
    }

    /**
     * @Route("/api/packages", methods={"GET"})
     * @Cache(maxage="300", smaxage="600")
     * @param QueryRequest $queryRequest
     * @param PaginationRequest $paginationRequest
     * @param Request $request
     * @return Response
     */
    public function packagesAction(
        QueryRequest $queryRequest,
        PaginationRequest $paginationRequest,
        Request $request
    ): Response {
        return $this->json(
            $this->packageSearchRepository->findLatestByQueryAndArchitecture(
                $paginationRequest->getOffset(),
                $paginationRequest->getLimit(),
                $queryRequest->getQuery(),
                // @TODO: Add Parameter Validation
                $request->get('architecture', Architecture::X86_64),
                $request->get('repository')
            ),
            Response::HTTP_OK,
            [],
            ['excludeDependencies' => true]
        );
    }
}
