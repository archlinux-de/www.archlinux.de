<?php

namespace App\Command\Update;

use App\Entity\Packages\Package;
use App\Entity\Packages\Repository;
use App\Exception\ValidationException;
use App\Repository\AbstractRelationRepository;
use App\Repository\PackageRepository;
use App\Repository\RepositoryRepository;
use App\Service\PackageDatabaseDownloader;
use App\Service\PackageDatabaseMirror;
use App\Service\PackageDatabaseReader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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

    /** @var PackageDatabaseReader */
    private $packageDatabaseReader;

    /** @var ValidatorInterface */
    private $validator;

    /** @var PackageDatabaseDownloader */
    private $packageDatabaseDownloader;

    /** @var PackageRepository */
    private $packageRepository;

    /**
     * @param EntityManagerInterface $entityManager
     * @param PackageDatabaseMirror $packageDatabaseMirror
     * @param RepositoryRepository $repositoryRepository
     * @param AbstractRelationRepository $relationRepository
     * @param PackageDatabaseReader $packageDatabaseReader
     * @param ValidatorInterface $validator
     * @param PackageDatabaseDownloader $packageDatabaseDownloader
     * @param PackageRepository $packageRepository
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        PackageDatabaseMirror $packageDatabaseMirror,
        RepositoryRepository $repositoryRepository,
        AbstractRelationRepository $relationRepository,
        PackageDatabaseReader $packageDatabaseReader,
        ValidatorInterface $validator,
        PackageDatabaseDownloader $packageDatabaseDownloader,
        PackageRepository $packageRepository
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->packageDatabaseMirror = $packageDatabaseMirror;
        $this->repositoryRepository = $repositoryRepository;
        $this->relationRepository = $relationRepository;
        $this->packageDatabaseReader = $packageDatabaseReader;
        $this->validator = $validator;
        $this->packageDatabaseDownloader = $packageDatabaseDownloader;
        $this->packageRepository = $packageRepository;
    }

    protected function configure(): void
    {
        $this->setName('app:update:packages');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->lock('packages.lock');
        ini_set('memory_limit', '4G');

        if ($this->packageDatabaseMirror->hasUpdated()) {
            $this->entityManager->beginTransaction();
            $updatedPackages = false;

            /** @var Repository $repository */
            foreach ($this->repositoryRepository->findAll() as $repository) {
                $packageDatabase = $this->packageDatabaseDownloader->download(
                    $repository->getName(),
                    $repository->getArchitecture()
                );
                $repositorySha256sum = (string)hash('sha256', $packageDatabase);

                if ($repositorySha256sum !== $repository->getSha256sum()) {
                    $repository->setSha256sum($repositorySha256sum);

                    $allPackageNames = [];
                    /** @var Package $package */
                    foreach (
                        $this->packageDatabaseReader->readPackages($repository, $packageDatabase) as $package
                    ) {
                        $errors = $this->validator->validate($package);
                        if ($errors->count() > 0) {
                            throw new ValidationException($errors);
                        }

                        $allPackageNames[] = $package->getName();

                        $persistedPackage = $this->packageRepository->findByRepositoryAndName(
                            $repository,
                            $package->getName()
                        );
                        if ($persistedPackage) {
                            if ($package->getSha256sum() !== $persistedPackage->getSha256sum()) {
                                $persistedPackage->update($package);
                                $updatedPackages = true;
                            }
                        } else {
                            $this->entityManager->persist($package);
                            $updatedPackages = true;
                        }
                    }

                    $obsoletePackages = $this->packageRepository->findByRepositoryExceptNames(
                        $repository,
                        $allPackageNames
                    );
                    foreach ($obsoletePackages as $obsoletePackage) {
                        $this->entityManager->remove($obsoletePackage);
                        $updatedPackages = true;
                    }
                }
            }

            if ($updatedPackages) {
                $this->entityManager->flush();
                $this->relationRepository->updateTargets();
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
            $this->packageDatabaseMirror->updateLastUpdate();
        }

        $this->release();

        return Command::SUCCESS;
    }
}
