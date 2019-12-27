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
        $package = $this->packageRepository->findByRepositoryAndName($repository, $databasePackage->getName());
        if ($package === null) {
            $package = Package::createFromPackageDatabase($repository, $databasePackage);
        } elseif ($package->getSha256sum() !== $databasePackage->getSha256sum()) {
            $package->updateFromPackageDatabase($databasePackage);
        } else {
            return false;
        }

        $this->entityManager->persist($package);
        return true;
    }

    /**
     * @param Repository $repository
     * @param string[] $allPackages
     * @return bool
     */
    public function cleanupObsoletePackages(Repository $repository, array $allPackages): bool
    {
        $obsoletePackages = $this->packageRepository->findByRepositoryExceptNames($repository, $allPackages);

        foreach ($obsoletePackages as $obsoletePackage) {
            $this->entityManager->remove($obsoletePackage);
        }

        return !empty($obsoletePackages);
    }
}
