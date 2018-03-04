<?php

namespace App\Command\Update;

use App\ArchLinux\Package as DatabasePackage;
use App\ArchLinux\PackageDatabaseMirror;
use App\Entity\Packages\Repository;
use App\Repository\AbstractRelationRepository;
use App\Repository\PackageRepository;
use App\Repository\RepositoryRepository;
use App\Service\PackageManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdatePackagesCommand extends ContainerAwareCommand
{
    use LockableTrait;

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

    /** @var PackageManager */
    private $packageManager;

    /**
     * @param EntityManagerInterface $entityManager
     * @param PackageDatabaseMirror $packageDatabaseMirror
     * @param RepositoryRepository $repositoryRepository
     * @param PackageRepository $packageRepository
     * @param AbstractRelationRepository $relationRepository
     * @param PackageManager $packageManager
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        PackageDatabaseMirror $packageDatabaseMirror,
        RepositoryRepository $repositoryRepository,
        PackageRepository $packageRepository,
        AbstractRelationRepository $relationRepository,
        PackageManager $packageManager
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->packageDatabaseMirror = $packageDatabaseMirror;
        $this->repositoryRepository = $repositoryRepository;
        $this->packageRepository = $packageRepository;
        $this->relationRepository = $relationRepository;
        $this->packageManager = $packageManager;
    }

    protected function configure()
    {
        $this->setName('app:update:packages');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->lock('cron.lock', true);
        ini_set('memory_limit', '-1');

        $updatedPackages = false;

        if ($this->packageDatabaseMirror->hasUpdated()) {
            /** @var Repository $repo */
            foreach ($this->repositoryRepository->findAll() as $repo) {
                $packageMTime = $this->packageRepository->getMaxMTimeByRepository($repo);

                $oldPackageNames = [];
                /** @var DatabasePackage $package */
                foreach ($this->packageManager->downloadPackagesForRepository($repo) as $package) {
                    if (is_null($packageMTime)
                        || $package->getMTime()->getTimestamp() > $packageMTime->getTimestamp()) {
                        $this->packageManager->updatePackage($repo, $package);
                    } else {
                        $oldPackageNames[] = $package->getName();
                    }
                    $updatedPackages = true;
                }

                if (!empty($oldPackageNames)) {
                    $this->entityManager->flush();
                    $this->packageManager->cleanupObsoletePackages($repo, $packageMTime, $oldPackageNames);
                }
            }
        }

        if ($updatedPackages) {
            $this->entityManager->flush();
            $this->relationRepository->updateTargets();
        }

        $this->entityManager->flush();
        $this->packageDatabaseMirror->updateLastUpdate();

        $this->release();
    }
}
