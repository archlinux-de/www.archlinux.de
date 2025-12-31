<?php

namespace App\Service;

use App\Repository\PackageRepository;
use Doctrine\ORM\NoResultException;

readonly class AppStreamDataVersionObtainer
{
    public function __construct(private PackageRepository $packageRepository)
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
}
