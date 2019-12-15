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
use Doctrine\DBAL\FetchMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @codeCoverageIgnore
 */
class ValidatePackagesCommand extends Command
{
    use LockableTrait;

    /** @var PackageDatabaseDownloader */
    private $packageDatabaseDownloader;

    /** @var RepositoryRepository */
    private $repositoryRepository;

    /** @var PackageRepository */
    private $packageRepository;

    /** @var EntityManagerInterface */
    private $entityManager;

    /**
     * @param PackageDatabaseDownloader $packageDatabaseDownloader
     * @param RepositoryRepository $repositoryRepository
     * @param PackageRepository $packageRepository
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        PackageDatabaseDownloader $packageDatabaseDownloader,
        RepositoryRepository $repositoryRepository,
        PackageRepository $packageRepository,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();
        $this->packageDatabaseDownloader = $packageDatabaseDownloader;
        $this->repositoryRepository = $repositoryRepository;
        $this->packageRepository = $packageRepository;
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this->setName('app:validate:packages');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->lock('packages.lock');
        ini_set('memory_limit', '-1');

        $result = 0;

        foreach ($this->findMissingPackages() as $repo => $package) {
            $output->writeln(sprintf('Missing package: %s [%s]', $package, $repo));
            $result = 1;
        }

        foreach ($this->findObsoletePackages() as $repo => $package) {
            $output->writeln(sprintf('Obsolete package: %s [%s]', $package, $repo));
            $result = 1;
        }

        foreach ($this->findOrphanedFiles() as $filesId) {
            $output->writeln(sprintf('Orphaned files: %d', $filesId));
            $result = 1;
        }

        foreach ($this->findOrphanedRelations() as $packageId) {
            $output->writeln(sprintf('Orphaned relation for package: %d', $packageId));
            $result = 1;
        }

        $this->release();

        return $result;
    }

    /**
     * @return iterable<string>
     */
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
     * @return iterable<DatabasePackage>
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
     * @return iterable<string>
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

    /**
     * @return int[]
     */
    private function findOrphanedFiles(): array
    {
        return $this
            ->entityManager
            ->getConnection()
            ->executeQuery(
                'SELECT files.id FROM files WHERE NOT EXISTS '
                . '(SELECT * FROM package WHERE files.id = package.files_id)'
            )
            ->fetchAll(FetchMode::COLUMN);
    }

    /**
     * @return int[]
     */
    private function findOrphanedRelations(): array
    {
        return $this
            ->entityManager
            ->getConnection()
            ->executeQuery(
                'SELECT packages_relation.source_id FROM packages_relation WHERE NOT EXISTS '
                . '(SELECT * FROM package WHERE packages_relation.source_id = package.id)'
            )
            ->fetchAll(FetchMode::COLUMN);
    }
}
