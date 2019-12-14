<?php

namespace App\Controller;

use App\Entity\Release;
use App\Repository\ReleaseRepository;
use DatatablesApiBundle\DatatablesColumnConfiguration;
use DatatablesApiBundle\DatatablesQuery;
use DatatablesApiBundle\DatatablesRequest;
use FeedIo\Factory;
use FeedIo\Feed;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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

        $jsonResponse = $this->json($response);
        // Only cache the first draw
        if ($response->getDraw() == 1) {
            $jsonResponse->setMaxAge(300);
            $jsonResponse->setSharedMaxAge(3600);
        }
        return $jsonResponse;
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

    /**
     * @Route("/releases/feed", methods={"GET"})
     * @Cache(smaxage="900")
     * @param Packages $assetPackages
     * @return Response
     */
    public function feedAction(Packages $assetPackages): Response
    {
        $feed = new Feed();
        $feedUrl = $this->generateUrl('app_releases_index', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $feed->setUrl($feedUrl);
        $feed->setTitle('Arch Linux Releases');
        $feed->setPublicId($feedUrl);
        $feed->setLink($this->generateUrl('app_releases_index', [], UrlGeneratorInterface::ABSOLUTE_URL));

        $icon = $feed->newElement();
        $icon->setName('icon')->setValue($assetPackages->getUrl('build/images/archicon.svg'));
        $feed->addElement($icon);

        $logo = $feed->newElement();
        $logo->setName('logo')->setValue($assetPackages->getUrl('build/images/archicon.svg'));
        $feed->addElement($logo);

        foreach ($this->releaseRepository->findAllAvailable() as $release) {
            $releaseUrl = $this->generateUrl(
                'app_releases_release',
                ['version' => $release->getVersion()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
            $item = $feed->newItem();
            $item->setPublicId($releaseUrl);
            $item->setTitle($release->getVersion());
            $item->setLastModified($release->getReleaseDate());
            $item->setLink($releaseUrl);
            $item->setDescription($release->getInfo());

            $feed->add($item);
        }

        $feedIo = Factory::create()->getFeedIo();
        return (new Response(
            $feedIo->toAtom($feed),
            Response::HTTP_OK,
            ['Content-Type' => 'application/atom+xml; charset=UTF-8']
        ));
    }
}
