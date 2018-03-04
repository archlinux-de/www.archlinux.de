<?php

namespace App\Service;

use App\ArchLinux\Package as DatabasePackage;
use App\ArchLinux\PackageDatabase;
use App\ArchLinux\PackageDatabaseDownloader;
use App\ArchLinux\PackageDatabaseMirror;
use App\ArchLinux\PackageDatabaseReader;
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

    /** @var PackageDatabaseMirror */
    private $packageDatabaseMirror;

    /** @var PackageRepository */
    private $packageRepository;

    /**
     * @param PackageDatabaseDownloader $packageDatabaseDownloader
     * @param EntityManagerInterface $entityManager
     * @param PackageDatabaseMirror $packageDatabaseMirror
     * @param PackageRepository $packageRepository
     */
    public function __construct(
        PackageDatabaseDownloader $packageDatabaseDownloader,
        EntityManagerInterface $entityManager,
        PackageDatabaseMirror $packageDatabaseMirror,
        PackageRepository $packageRepository
    ) {
        $this->packageDatabaseDownloader = $packageDatabaseDownloader;
        $this->entityManager = $entityManager;
        $this->packageDatabaseMirror = $packageDatabaseMirror;
        $this->packageRepository = $packageRepository;
    }

    /**
     * @param Repository $repository
     * @return iterable
     */
    public function downloadPackagesForRepository(Repository $repository): iterable
    {
        $packageDatabaseFile = $this->packageDatabaseDownloader->download(
            $this->packageDatabaseMirror->getMirrorUrl(),
            $repository->getName(),
            $repository->getArchitecture()
        );

        if (($repository->getMTime() && $packageDatabaseFile->getMTime() > $repository->getMTime()->getTimestamp())
            || !$repository->getMTime()) {
            $repository->setMTime((new \DateTime())->setTimestamp($packageDatabaseFile->getMTime()));
            /** @TODO Should not persist here */
            $this->entityManager->persist($repository);
            yield from new PackageDatabase(new PackageDatabaseReader($packageDatabaseFile));
        }
    }

    /**
     * @param Repository $repository
     * @param DatabasePackage $databasePackage
     * @internal param Package $package
     */
    public function updatePackage(Repository $repository, DatabasePackage $databasePackage)
    {
        $package = $this->packageRepository->findByRepositoryAndName($repository, $databasePackage->getName());
        if (is_null($package)) {
            $package = Package::createFromPackageDatabase($repository, $databasePackage);
        } else {
            $package->updateFromPackageDatabase($databasePackage);
        }

        $this->entityManager->persist($package);
    }

    /**
     * @param Repository $repo
     * @param \DateTime $packageMTime
     * @param array $allPackages
     */
    public function cleanupObsoletePackages(Repository $repo, \DateTime $packageMTime, array $allPackages)
    {
        $repoPackages = $this->packageRepository->findByRepositoryOlderThan($repo, $packageMTime);

        /** @var Package $repoPackage */
        foreach ($repoPackages as $repoPackage) {
            if (!in_array($repoPackage->getName(), $allPackages)) {
                $this->entityManager->remove($repoPackage);
            }
        }
    }
}
