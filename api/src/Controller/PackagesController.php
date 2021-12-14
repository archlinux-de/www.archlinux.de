<?php

namespace App\Controller;

use App\Entity\Packages\Package;
use App\Repository\PackageRepository;
use App\Request\ArchitectureRequest;
use App\Request\PaginationRequest;
use App\Request\QueryRequest;
use App\Request\RepositoryRequest;
use App\Request\TermRequest;
use App\SearchRepository\PackageSearchRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

    #[Route(path: '/packages/opensearch', methods: ['GET'])]
    #[Cache(maxage: 300, smaxage: 600)]
    public function openSearchAction(): Response
    {
        $response = $this->render('packages/opensearch.xml.twig');
        $response->headers->set('Content-Type', 'application/opensearchdescription+xml; charset=UTF-8');
        return $response;
    }

    #[Route(path: '/packages/feed', methods: ['GET'])]
    #[Cache(maxage: 300, smaxage: 600)]
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

    #[Route(path: '/packages/suggest', methods: ['GET'])]
    #[Cache(maxage: 300, smaxage: 600)]
    public function suggestAction(TermRequest $termRequest): Response
    {
        $suggestions = $this->packageSearchRepository->findByTerm($termRequest->getTerm(), 10);

        return $this->json(array_map(fn(Package $package): string => $package->getName(), $suggestions));
    }

    #[Route(path: '/api/packages', methods: ['GET'])]
    #[Cache(maxage: 300, smaxage: 600)]
    public function packagesAction(
        QueryRequest $queryRequest,
        PaginationRequest $paginationRequest,
        ArchitectureRequest $architectureRequest,
        RepositoryRequest $repositoryRequest
    ): Response {
        return $this->json(
            $this->packageSearchRepository->findLatestByQueryAndArchitecture(
                $paginationRequest->getOffset(),
                $paginationRequest->getLimit(),
                $queryRequest->getQuery(),
                $architectureRequest->getArchitecture(),
                $repositoryRequest->getRepository()
            )
        );
    }
}
