<?php

namespace App\Service;

use App\Repository\PackageRepository;
use Doctrine\ORM\NoResultException;

readonly class AppStreamDataHelper
{
    public function __construct(
        private string $appStreamDataBaseUrl,
        private string $appStreamDataFile,
        private PackageRepository $packageRepository
    )
    {
    }

    /**
     * @throws NoResultException
     */
    public function obtainAppStreamDataVersion(): string
    {

        $appStreamData = $this->packageRepository->getByName(
            'extra',
            'x86_64',
            'archlinux-appstream-data'
        );

        // version is provided as YYYYMMDD-n
        return explode('-', $appStreamData->getVersion())[0];
    }

    public function buildUpstreamUrl(
        string $version,
        string $repoName
    ): string
    {
        return
            $this->appStreamDataBaseUrl .
            '/' .
            $version .
            '/' .
            $repoName .
            '/' .
            $this->appStreamDataFile;
    }
}
