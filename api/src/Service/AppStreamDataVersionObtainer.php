<?php

namespace App\Service;

use App\Exception\AppStreamDataPackageNotFoundException;
use App\Repository\PackageRepository;
use Doctrine\ORM\NoResultException;

readonly class AppStreamDataVersionObtainer
{
    public function __construct(private PackageRepository $packageRepository)
    {
    }

    /**
     * @throws AppStreamDataPackageNotFoundException
     */
    public function obtainAppStreamDataVersion(): string
    {
        try {
            $appStreamData = $this->packageRepository->getByName(
                'extra',
                'x86_64',
                'archlinux-appstream-data'
            );
        } catch (NoResultException $e) {
            throw new AppStreamDataPackageNotFoundException(
                'archlinux-appstream-data package not found in database, please run app:update:packages command',
                0,
                $e
            );
        }

        // version is provided as YYYYMMDD-n
        return explode('-', $appStreamData->getVersion())[0];
    }
}
