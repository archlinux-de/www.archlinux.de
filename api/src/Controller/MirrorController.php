<?php

namespace App\Controller;

use App\Entity\Mirror;
use App\Repository\PackageRepository;
use App\Repository\ReleaseRepository;
use App\SearchRepository\MirrorSearchRepository;
use App\Service\GeoIp;
use Doctrine\ORM\UnexpectedResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MirrorController extends AbstractController
{
    /** @var GeoIp */
    private $geoIp;

    /** @var MirrorSearchRepository */
    private $mirrorSearchRepository;

    /** @var string */
    private $mirrorCountry;

    /** @var string */
    private $mirrorArchive;

    /**
     * @param GeoIp $geoIp
     * @param string $mirrorCountry
     * @param MirrorSearchRepository $mirrorSearchRepository
     * @param string $mirrorArchive
     */
    public function __construct(
        GeoIp $geoIp,
        string $mirrorCountry,
        MirrorSearchRepository $mirrorSearchRepository,
        string $mirrorArchive
    ) {
        $this->geoIp = $geoIp;
        $this->mirrorCountry = $mirrorCountry;
        $this->mirrorSearchRepository = $mirrorSearchRepository;
        $this->mirrorArchive = $mirrorArchive;
    }

    /**
     * @Route(
     *     "/download/iso/{version}/{file}",
     *      requirements={
     *          "version"= "[\w\.\-]{1,191}",
     *          "file"= "[\w\.\-\+/:]{0,255}"
     *      },
     *      methods={"GET"}
     *     )
     * @param string $version
     * @param string $file
     * @param Request $request
     * @param ReleaseRepository $releaseRepository
     * @return Response
     */
    public function isoAction(
        string $version,
        string $file,
        Request $request,
        ReleaseRepository $releaseRepository
    ): Response {
        try {
            $release = $releaseRepository->getByVersion($version);
        } catch (UnexpectedResultException $e) {
            throw $this->createNotFoundException('ISO image not found', $e);
        }

        if ($release->isAvailable()) {
            return $this->redirectToMirror('iso/' . $version . '/' . $file, $release->getCreated(), $request);
        } else {
            return $this->redirect(
                $this->mirrorArchive . 'iso/' . $this->createDirectoryVersion($version) . '/' . $file
            );
        }
    }

    private function createDirectoryVersion(string $version): string
    {
        return match ($version) {
            '0.7.1', '0.7.2' => '0.7',
            '2007.08-2' => '2007.08',
            default => $version,
        };
    }

    /**
     * @param string $file
     * @param \DateTime|null $lastsync
     * @param Request $request
     * @return Response
     */
    private function redirectToMirror(string $file, ?\DateTime $lastsync, Request $request): Response
    {
        return $this->redirect(
            $this->getMirror($lastsync, $request->getClientIp() ?? '')->getUrl() . $file
        );
    }

    /**
     * @param \DateTime|null $lastSync
     * @param string $clientIp
     * @return Mirror
     */
    private function getMirror(?\DateTime $lastSync, string $clientIp): Mirror
    {
        $countryCode = $this->geoIp->getCountryCode($clientIp);
        if (empty($countryCode)) {
            $countryCode = $this->mirrorCountry;
        }
        $mirrors = $this->mirrorSearchRepository->findBestByCountryAndLastSync($countryCode, $lastSync);

        if (empty($mirrors)) {
            throw $this->createNotFoundException('Mirror not found');
        }

        mt_srand(crc32($clientIp));
        $randomMirrorIndex = array_rand($mirrors, 1);
        return $mirrors[$randomMirrorIndex];
    }

    /**
     * @Route(
     *     "/download/{repository}/os/{architecture}/{file}",
     *      requirements={
     *          "file"= "^[^-]+.*-[^-]+-[^-]+-[a-zA-Z0-9@\.\-\+_:]{1,255}$"
     *      },
     *      methods={"GET"}
     *     )
     * @param string $repository
     * @param string $architecture
     * @param string $file
     * @param Request $request
     * @param PackageRepository $packageRepository
     * @return Response
     */
    public function packageAction(
        string $repository,
        string $architecture,
        string $file,
        Request $request,
        PackageRepository $packageRepository
    ): Response {
        preg_match('#^([^-]+.*)-[^-]+-[^-]+-.*$#', $file, $matches);
        try {
            $package = $packageRepository->getByName($repository, $architecture, $matches[1]);
        } catch (UnexpectedResultException $e) {
            throw $this->createNotFoundException('Package not found', $e);
        }

        return $this->redirectToMirror(
            $repository . '/os/' . $architecture . '/' . $file,
            $package->getBuildDate(),
            $request
        );
    }

    /**
     * @Route("/download/{file}", requirements={"file"= "^[a-zA-Z0-9@\.\-\+_/:]{1,255}$"}, methods={"GET"})
     * @param string $file
     * @param Request $request
     * @return Response
     */
    public function fallbackAction(string $file, Request $request): Response
    {
        return $this->redirectToMirror($file, null, $request);
    }
}
