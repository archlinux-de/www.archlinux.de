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
    public function __construct(
        private PackageRepository $packageRepository,
        private PackageSearchRepository $packageSearchRepository,
        private string $defaultArchitecture
    ) {
    }

    /**
     * @Route("/packages/opensearch", methods={"GET"})
     * @Cache(maxage="300", smaxage="600")
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
     */
    public function feedAction(): Response
    {
        $packages = $this->packageRepository->findLatestByArchitecture($this->defaultArchitecture, 25);

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
            )
        );
    }
}
