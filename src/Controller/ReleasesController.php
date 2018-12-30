<?php

namespace App\Controller;

use App\Entity\Release;
use App\Repository\ReleaseRepository;
use DatatablesApiBundle\DatatablesColumnConfiguration;
use DatatablesApiBundle\DatatablesQuery;
use DatatablesApiBundle\DatatablesRequest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReleasesController extends AbstractController
{
    /** @var ReleaseRepository */
    private $releaseRepository;

    /** @var DatatablesQuery */
    private $datatablesQuery;

    /**
     * @param ReleaseRepository $releaseRepository
     * @param DatatablesQuery $datatablesQuery
     */
    public function __construct(ReleaseRepository $releaseRepository, DatatablesQuery $datatablesQuery)
    {
        $this->releaseRepository = $releaseRepository;
        $this->datatablesQuery = $datatablesQuery;
    }

    /**
     * @Route("/releases", methods={"GET"})
     * @Cache(smaxage="900")
     * @param Request $request
     * @return Response
     */
    public function indexAction(Request $request): Response
    {
        $search = $request->get('search');
        return $this->render(
            'releases/index.html.twig',
            ['search' => $search]
        );
    }

    /**
     * @Route("/releases/datatables", methods={"GET"})
     * @param DatatablesRequest $request
     * @return Response
     */
    public function datatablesAction(DatatablesRequest $request): Response
    {
        $columnConfiguration = (new DatatablesColumnConfiguration())
            ->addTextSearchableColumn('version', 'release.version')
            ->addTextSearchableColumn('kernelVersion', 'release.kernelVersion')
            ->addTextSearchableColumn('info', 'release.info')
            ->addOrderableColumn('version', 'release.version')
            ->addOrderableColumn('releaseDate', 'release.releaseDate');
        $response = $this->datatablesQuery->getResult(
            $request,
            $columnConfiguration,
            $this->releaseRepository
                ->createQueryBuilder('release'),
            $this->releaseRepository->getSize()
        );

        return $this->json($response);
    }

    /**
     * @Route("/releases/{version}", methods={"GET"}, requirements={"version": "^[0-9]+[\.\-\w]+$"})
     * @Cache(smaxage="900")
     * @param Release $release
     * @return Response
     */
    public function releaseAction(Release $release): Response
    {
        return $this->render(
            'releases/release.html.twig',
            ['release' => $release]
        );
    }
}
