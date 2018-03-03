<?php

namespace App\Command\Update;

use App\ArchLinux\Package as DatabasePackage;
use App\ArchLinux\PackageDatabase;
use App\ArchLinux\PackageDatabaseDownloader;
use App\ArchLinux\PackageDatabaseMirror;
use App\ArchLinux\PackageDatabaseReader;
use App\Entity\Packages\Package;
use App\Entity\Packages\Repository;
use App\Repository\AbstractRelationRepository;
use App\Repository\PackageRepository;
use App\Repository\RepositoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdatePackagesCommand extends ContainerAwareCommand
{
    use LockableTrait;

    /** @var bool */
    private $updatedPackages = false;

    /** @var PackageDatabaseDownloader */
    private $packageDatabaseDownloader;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var PackageDatabaseMirror */
    private $packageDatabaseMirror;

    /** @var RepositoryRepository */
    private $repositoryRepository;

    /** @var PackageRepository */
    private $packageRepository;

    /** @var AbstractRelationRepository */
    private $relationRepository;

    /**
     * @param PackageDatabaseDownloader $packageDatabaseDownloader
     * @param EntityManagerInterface $entityManager
     * @param PackageDatabaseMirror $packageDatabaseMirror
     * @param RepositoryRepository $repositoryRepository
     * @param PackageRepository $packageRepository
     * @param AbstractRelationRepository $relationRepository
     */
    public function __construct(
        PackageDatabaseDownloader $packageDatabaseDownloader,
        EntityManagerInterface $entityManager,
        PackageDatabaseMirror $packageDatabaseMirror,
        RepositoryRepository $repositoryRepository,
        PackageRepository $packageRepository,
        AbstractRelationRepository $relationRepository
    ) {
        parent::__construct();
        $this->packageDatabaseDownloader = $packageDatabaseDownloader;
        $this->entityManager = $entityManager;
        $this->packageDatabaseMirror = $packageDatabaseMirror;
        $this->repositoryRepository = $repositoryRepository;
        $this->packageRepository = $packageRepository;
        $this->relationRepository = $relationRepository;
    }

    protected function configure()
    {
        $this->setName('app:update:packages');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->lock('cron.lock', true);
        ini_set('memory_limit', '-1');

        if ($this->packageDatabaseMirror->hasUpdated()) {
            /** @var Repository $repo */
            foreach ($this->repositoryRepository->findAll() as $repo) {
                $packageMTime = $this->packageRepository->getMaxMTimeByRepository($repo);

                $oldPackageNames = [];
                /** @var DatabasePackage $package */
                foreach ($this->downloadPackagesForRepository($repo) as $package) {
                    if (is_null($packageMTime)
                        || $package->getMTime()->getTimestamp() > $packageMTime->getTimestamp()) {
                        $this->updatePackage($repo, $package);
                    } else {
                        $oldPackageNames[] = $package->getName();
                    }
                }

                if (!empty($oldPackageNames)) {
                    $this->entityManager->flush();
                    $this->cleanupObsoletePackages($repo, $packageMTime, $oldPackageNames);
                }
            }
        }

        if ($this->updatedPackages) {
            $this->entityManager->flush();
            $this->relationRepository->updateTargets();
        }

        $this->entityManager->flush();
        $this->packageDatabaseMirror->updateLastUpdate();

        $this->release();
    }

    /**
     * @param Repository $repository
     * @return iterable
     */
    private function downloadPackagesForRepository(Repository $repository): iterable
    {
        $packageDatabaseFile = $this->packageDatabaseDownloader->download(
            $this->packageDatabaseMirror->getMirrorUrl(),
            $repository->getName(),
            $repository->getArchitecture()
        );

        if (($repository->getMTime() && $packageDatabaseFile->getMTime() > $repository->getMTime()->getTimestamp())
            || !$repository->getMTime()) {
            $repository->setMTime((new \DateTime())->setTimestamp($packageDatabaseFile->getMTime()));
            $this->entityManager->persist($repository);
            yield from new PackageDatabase(new PackageDatabaseReader($packageDatabaseFile));
        }
    }

    /**
     * @param Repository $repository
     * @param DatabasePackage $databasePackage
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @internal param Package $package
     */
    private function updatePackage(Repository $repository, DatabasePackage $databasePackage)
    {
        $package = $this->packageRepository->findByRepositoryAndName($repository, $databasePackage->getName());
        if (is_null($package)) {
            $package = Package::createFromPackageDatabase($repository, $databasePackage);
        } else {
            $package->updateFromPackageDatabase($databasePackage);
        }

        $this->entityManager->persist($package);

        $this->updatedPackages = true;
    }

    /**
     * @param Repository $repo
     * @param \DateTime $packageMTime
     * @param array $allPackages
     */
    private function cleanupObsoletePackages(Repository $repo, \DateTime $packageMTime, array $allPackages)
    {
        $repoPackages = $this->packageRepository->findByRepositoryOlderThan($repo, $packageMTime);

        /** @var Package $repoPackage */
        foreach ($repoPackages as $repoPackage) {
            if (!in_array($repoPackage->getName(), $allPackages)) {
                $this->entityManager->remove($repoPackage);
                $this->updatedPackages = true;
            }
        }
    }
}
