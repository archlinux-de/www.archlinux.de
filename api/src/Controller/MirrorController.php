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
use Symfony\Component\Routing\Attribute\Route;

class MirrorController extends AbstractController
{
    public function __construct(
        private readonly GeoIp $geoIp,
        private readonly string $mirrorCountry,
        private readonly MirrorSearchRepository $mirrorSearchRepository,
        private readonly string $mirrorArchive
    ) {
    }

    #[Route(
        path: '/download/iso/{version}/{file}',
        requirements: [
            'version' => '[\w\.\-]{1,191}',
            'file' => '[\w\.\-\+/:]{0,255}'
        ],
        methods: ['GET']
    )]
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

    private function redirectToMirror(string $file, ?\DateTime $lastsync, Request $request): Response
    {
        return $this->redirect(
            $this->getMirror($lastsync, $request->getClientIp() ?? '')->getUrl() . $file
        );
    }

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
        $randomMirrorIndex = array_rand($mirrors);
        return $mirrors[$randomMirrorIndex];
    }

    #[Route(
        path: '/download/{repository}/os/{architecture}/{file}',
        requirements: ['file' => '^[^-]+.*-[^-]+-[^-]+-[a-zA-Z0-9@\.\-\+_:]{1,255}$'],
        methods: ['GET']
    )]
    public function packageAction(
        string $repository,
        string $architecture,
        string $file,
        Request $request,
        PackageRepository $packageRepository
    ): Response {
        preg_match('#^([^-]+.*)-[^-]+-[^-]+-.*$#', $file, $matches);
        try {
            assert(isset($matches[1]));
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

    #[Route(
        path: '/download/{file}',
        requirements: ['file' => '^[a-zA-Z0-9@\.\-\+_/:]{1,255}$'],
        methods: ['GET']
    )]
    public function fallbackAction(string $file, Request $request): Response
    {
        return $this->redirectToMirror($file, null, $request);
    }
}
