<?php

namespace App\Command\Update;

use App\ArchLinux\Package as DatabasePackage;
use App\ArchLinux\PackageDatabaseMirror;
use App\Entity\Packages\Repository;
use App\Repository\AbstractRelationRepository;
use App\Repository\RepositoryRepository;
use App\Service\PackageManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdatePackagesCommand extends Command
{
    use LockableTrait;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var PackageDatabaseMirror */
    private $packageDatabaseMirror;

    /** @var RepositoryRepository */
    private $repositoryRepository;

    /** @var AbstractRelationRepository */
    private $relationRepository;

    /** @var PackageManager */
    private $packageManager;

    /**
     * @param EntityManagerInterface $entityManager
     * @param PackageDatabaseMirror $packageDatabaseMirror
     * @param RepositoryRepository $repositoryRepository
     * @param AbstractRelationRepository $relationRepository
     * @param PackageManager $packageManager
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        PackageDatabaseMirror $packageDatabaseMirror,
        RepositoryRepository $repositoryRepository,
        AbstractRelationRepository $relationRepository,
        PackageManager $packageManager
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->packageDatabaseMirror = $packageDatabaseMirror;
        $this->repositoryRepository = $repositoryRepository;
        $this->relationRepository = $relationRepository;
        $this->packageManager = $packageManager;
    }

    protected function configure(): void
    {
        $this->setName('app:update:packages');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->lock('cron.lock', true);
        ini_set('memory_limit', '-1');

        $updatedPackages = false;

        if ($this->packageDatabaseMirror->hasUpdated()) {
            /** @var Repository $repository */
            foreach ($this->repositoryRepository->findAll() as $repository) {
                $allPackageNames = [];
                $packageRepositoryGenerator = $this->packageManager->downloadPackagesForRepository($repository);
                /** @var DatabasePackage $package */
                foreach ($packageRepositoryGenerator as $package) {
                    $allPackageNames[] = $package->getName();
                    if ($this->packageManager->updatePackage($repository, $package)) {
                        $updatedPackages = true;
                    }
                }

                if ($packageRepositoryGenerator->getReturn()) {
                    $this->entityManager->flush();
                    if ($this->packageManager->cleanupObsoletePackages($repository, $allPackageNames)) {
                        $updatedPackages = true;
                    }
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
