<?php

namespace App\Command\Validate;

use App\ArchLinux\Package as DatabasePackage;
use App\ArchLinux\PackageDatabase;
use App\ArchLinux\PackageDatabaseDownloader;
use App\ArchLinux\PackageDatabaseReader;
use App\Entity\Packages\Package;
use App\Entity\Packages\Repository;
use App\Repository\PackageRepository;
use App\Repository\RepositoryRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ValidatePackagesCommand extends ContainerAwareCommand
{
    use LockableTrait;

    /** @var PackageDatabaseDownloader */
    private $packageDatabaseDownloader;

    /** @var RepositoryRepository */
    private $repositoryRepository;

    /** @var PackageRepository */
    private $packageRepository;

    /**
     * @param PackageDatabaseDownloader $packageDatabaseDownloader
     * @param RepositoryRepository $repositoryRepository
     * @param PackageRepository $packageRepository
     */
    public function __construct(
        PackageDatabaseDownloader $packageDatabaseDownloader,
        RepositoryRepository $repositoryRepository,
        PackageRepository $packageRepository
    ) {
        parent::__construct();
        $this->packageDatabaseDownloader = $packageDatabaseDownloader;
        $this->repositoryRepository = $repositoryRepository;
        $this->packageRepository = $packageRepository;
    }

    protected function configure()
    {
        $this->setName('app:validate:packages');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->lock('cron.lock', true);
        ini_set('memory_limit', '-1');

        $result = 0;

        /** @var DatabasePackage $package */

        foreach ($this->findMissingPackages() as $repo => $package) {
            $output->writeln(sprintf('Missing package: %s [%s]', $package, $repo));
            $result = 1;
        }

        foreach ($this->findObsoletePackages() as $repo => $package) {
            $output->writeln(sprintf('Obsolete package: %s [%s]', $package, $repo));
            $result = 1;
        }

        $this->release();

        return $result;
    }

    private function findMissingPackages(): iterable
    {
        /** @var Repository $repo */
        foreach ($this->repositoryRepository->findAll() as $repo) {
            /** @var DatabasePackage $package */
            foreach ($this->downloadPackagesForRepository($repo) as $package) {
                if (is_null($this->packageRepository->findByRepositoryAndName($repo, $package->getName()))) {
                    yield $repo->getName() => $package->getName();
                }
            }
        }
    }

    /**
     * @param Repository $repository
     * @return iterable
     */
    private function downloadPackagesForRepository(Repository $repository): iterable
    {
        $packageDatabaseFile = $this->packageDatabaseDownloader->download(
            $repository->getName(),
            $repository->getArchitecture()
        );
        yield from new PackageDatabase(new PackageDatabaseReader($packageDatabaseFile));
    }

    /**
     * @return iterable
     */
    private function findObsoletePackages(): iterable
    {
        /** @var Repository $repo */
        foreach ($this->repositoryRepository->findAll() as $repo) {
            $packageNames = [];
            /** @var DatabasePackage $package */
            foreach ($this->downloadPackagesForRepository($repo) as $package) {
                $packageNames[] = $package->getName();
            }

            $repoPackages = $this->packageRepository->findByRepository($repo);
            /** @var Package $repoPackage */
            foreach ($repoPackages as $repoPackage) {
                if (!in_array($repoPackage->getName(), $packageNames)) {
                    yield $repo->getName() => $repoPackage->getName();
                }
            }
        }
    }
}
