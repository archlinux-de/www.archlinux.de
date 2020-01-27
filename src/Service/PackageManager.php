<?php

namespace App\Service;

use App\ArchLinux\Package as DatabasePackage;
use App\ArchLinux\PackageDatabaseDownloader;
use App\Entity\Packages\Package;
use App\Entity\Packages\Repository;
use App\Repository\PackageRepository;
use Doctrine\ORM\EntityManagerInterface;

class PackageManager
{
    /** @var PackageDatabaseDownloader */
    private $packageDatabaseDownloader;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var PackageRepository */
    private $packageRepository;

    /** @var array<\DateTime|null> */
    private $packageMTimes;

    /**
     * @param PackageDatabaseDownloader $packageDatabaseDownloader
     * @param EntityManagerInterface $entityManager
     * @param PackageRepository $packageRepository
     */
    public function __construct(
        PackageDatabaseDownloader $packageDatabaseDownloader,
        EntityManagerInterface $entityManager,
        PackageRepository $packageRepository
    ) {
        $this->packageDatabaseDownloader = $packageDatabaseDownloader;
        $this->entityManager = $entityManager;
        $this->packageRepository = $packageRepository;
    }

    /**
     * @param Repository $repository
     * @return \Generator<DatabasePackage>
     */
    public function downloadPackagesForRepository(Repository $repository): \Generator
    {
        $packageDatabaseFile = $this->packageDatabaseDownloader->download(
            $repository->getName(),
            $repository->getArchitecture()
        );
        $sha256sum = hash_file('sha256', (string)$packageDatabaseFile->getRealPath());
        if ($sha256sum !== $repository->getSha256sum()) {
            $repository->setSha256sum($sha256sum);
            /** @TODO Should not persist here */
            $this->entityManager->persist($repository);
            yield from $this->packageDatabaseDownloader->createDatabase($packageDatabaseFile);
            return true;
        }
        return false;
    }

    /**
     * @param Repository $repository
     * @param DatabasePackage $databasePackage
     * @return bool
     */
    public function updatePackage(
        Repository $repository,
        DatabasePackage $databasePackage
    ): bool {
        $packageMTime = $this->getRepositoryPackageMTime($repository);

        if (
            $packageMTime === null
            || $databasePackage->getMTime()->getTimestamp() > $packageMTime->getTimestamp()
        ) {
            $package = $this->packageRepository->findByRepositoryAndName($repository, $databasePackage->getName());
            if ($package === null) {
                $package = Package::createFromPackageDatabase($repository, $databasePackage);
            } else {
                $package->updateFromPackageDatabase($databasePackage);
            }
            $this->entityManager->persist($package);
            return true;
        }
        return false;
    }

    /**
     * @param Repository $repository
     * @return \DateTime|null
     */
    private function getRepositoryPackageMTime(Repository $repository): ?\DateTime
    {
        if (!isset($this->packageMTimes[$repository->getId()])) {
            $this->packageMTimes[$repository->getId()] = $this->packageRepository->getMaxMTimeByRepository($repository);
        }
        return $this->packageMTimes[$repository->getId()];
    }

    /**
     * @param Repository $repository
     * @param string[] $allPackages
     * @return bool
     */
    public function cleanupObsoletePackages(Repository $repository, array $allPackages): bool
    {
        $packageMTime = $this->getRepositoryPackageMTime($repository);
        if ($packageMTime === null) {
            $repoPackages = $this->packageRepository->findByRepository($repository);
        } else {
            $repoPackages = $this->packageRepository->findByRepositoryOlderThan($repository, $packageMTime);
        }
        $packagesRemoved = false;

        /** @var Package $repoPackage */
        foreach ($repoPackages as $repoPackage) {
            if (!in_array($repoPackage->getName(), $allPackages)) {
                $this->entityManager->remove($repoPackage);
                $packagesRemoved = true;
            }
        }

        return $packagesRemoved;
    }
}
