<?php

namespace App\Controller;

use App\Entity\Mirror;
use App\Repository\MirrorRepository;
use App\Repository\PackageRepository;
use App\Repository\ReleaseRepository;
use App\Service\GeoIp;
use Doctrine\ORM\UnexpectedResultException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MirrorController extends Controller
{
    /** @var GeoIp */
    private $geoIp;
    /** @var MirrorRepository */
    private $mirrorRepository;

    /**
     * @param GeoIp $geoIp
     * @param MirrorRepository $mirrorRepository
     */
    public function __construct(GeoIp $geoIp, MirrorRepository $mirrorRepository)
    {
        $this->geoIp = $geoIp;
        $this->mirrorRepository = $mirrorRepository;
    }

    /**
     * @Route(
     *     "/download/iso/{version}/{file}",
     *      requirements={
     *          "version": "^[0-9]{4}\.[0-9]{2}\.[0-9]{2}$",
     *          "file": "[a-zA-Z0-9\.\-\+_/:]{1,255}"
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
            $release = $releaseRepository->getAvailableByVersion($version);
        } catch (UnexpectedResultException $e) {
            throw $this->createNotFoundException('ISO image not found', $e);
        }

        return $this->redirectToMirror('iso/' . $version . '/' . $file, $release->getCreated(), $request);
    }

    /**
     * @param string $file
     * @param \DateTime|int $lastsync
     * @param Request $request
     * @return Response
     */
    private function redirectToMirror(string $file, \DateTime $lastsync, Request $request): Response
    {
        return $this->redirect(
            $this->getMirror($lastsync, $request->getClientIp())->getUrl() . $file
        );
    }

    /**
     * @param \DateTime|int $lastSync
     *
     * @param string $clientIp
     * @return Mirror
     */
    private function getMirror(\DateTime $lastSync, string $clientIp): Mirror
    {
        $countryCode = $this->geoIp->getCountryCode($clientIp);
        if (empty($countryCode)) {
            $countryCode = $this->getParameter('app.mirrors.country');
        }
        $mirrors = $this->mirrorRepository->findBestByCountryAndLastSync($countryCode, $lastSync);

        if (empty($mirrors)) {
            throw $this->createNotFoundException('Mirror not found');
        }

        srand(crc32($clientIp));
        return $mirrors[array_rand($mirrors, 1)];
    }

    /**
     * @Route(
     *     "/download/{repository}/os/{architecture}/{file}",
     *      requirements={
     *          "file": "^[^-]+.*-[^-]+-[^-]+-[a-zA-Z0-9\.\-\+_:]{1,255}$"
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
            $package->getMTime(),
            $request
        );
    }

    /**
     * @Route("/download/{file}", requirements={"file": "^[a-zA-Z0-9\.\-\+_/:]{1,255}$"}, methods={"GET"})
     * @param string $file
     * @param Request $request
     * @return Response
     */
    public function fallbackAction(string $file, Request $request): Response
    {
        return $this->redirectToMirror($file, new \DateTime('yesterday'), $request);
    }
}
