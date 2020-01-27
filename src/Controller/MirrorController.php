<?php

namespace App\Controller;

use App\Entity\Mirror;
use App\Repository\MirrorRepository;
use App\Repository\PackageRepository;
use App\Repository\ReleaseRepository;
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

    /** @var MirrorRepository */
    private $mirrorRepository;

    /** @var string */
    private $mirrorCountry;

    /**
     * @param GeoIp $geoIp
     * @param MirrorRepository $mirrorRepository
     * @param string $mirrorCountry
     */
    public function __construct(GeoIp $geoIp, MirrorRepository $mirrorRepository, string $mirrorCountry)
    {
        $this->geoIp = $geoIp;
        $this->mirrorRepository = $mirrorRepository;
        $this->mirrorCountry = $mirrorCountry;
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
     * @param \DateTime $lastsync
     * @param Request $request
     * @return Response
     */
    private function redirectToMirror(string $file, \DateTime $lastsync, Request $request): Response
    {
        return $this->redirect(
            $this->getMirror($lastsync, $request->getClientIp() ?? '')->getUrl() . $file
        );
    }

    /**
     * @param \DateTime $lastSync
     * @param string $clientIp
     * @return Mirror
     */
    private function getMirror(\DateTime $lastSync, string $clientIp): Mirror
    {
        $countryCode = $this->geoIp->getCountryCode($clientIp);
        if (empty($countryCode)) {
            $countryCode = $this->mirrorCountry;
        }
        $mirrors = $this->mirrorRepository->findBestByCountryAndLastSync($countryCode, $lastSync);

        if (empty($mirrors)) {
            throw $this->createNotFoundException('Mirror not found');
        }

        mt_srand(crc32($clientIp));
        $randomMirrorIndex = array_rand($mirrors, 1);
        if (is_array($randomMirrorIndex)) {
            // @codeCoverageIgnoreStart
            $randomMirrorIndex = 0;
            // @codeCoverageIgnoreEnd
        }
        return $mirrors[$randomMirrorIndex];
    }

    /**
     * @Route(
     *     "/download/{repository}/os/{architecture}/{file}",
     *      requirements={
     *          "file": "^[^-]+.*-[^-]+-[^-]+-[a-zA-Z0-9@\.\-\+_:]{1,255}$"
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
            $package->getBuildDate() ?? new \DateTime('-1 day'),
            $request
        );
    }

    /**
     * @Route("/download/{file}", requirements={"file": "^[a-zA-Z0-9@\.\-\+_/:]{1,255}$"}, methods={"GET"})
     * @param string $file
     * @param Request $request
     * @return Response
     */
    public function fallbackAction(string $file, Request $request): Response
    {
        return $this->redirectToMirror($file, new \DateTime('yesterday'), $request);
    }
}
