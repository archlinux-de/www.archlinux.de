<?php

namespace App\Command\Update;

use App\ArchLinux\Package as DatabasePackage;
use App\ArchLinux\PackageDatabase;
use App\ArchLinux\PackageDatabaseDownloader;
use App\ArchLinux\PackageDatabaseMirror;
use App\ArchLinux\PackageDatabaseReader;
use App\Entity\Packages\Package;
use App\Entity\Packages\Relations\AbstractRelation;
use App\Entity\Packages\Repository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdatePackagesCommand extends ContainerAwareCommand
{
    use LockableTrait;

    /** @var bool */
    private $updatedPackages = false;

    /** @var CacheItemPoolInterface */
    private $cache;

    /** @var PackageDatabaseDownloader */
    private $packageDatabaseDownloader;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var PackageDatabaseMirror */
    private $packageDatabaseMirror;

    /**
     * @param PackageDatabaseDownloader $packageDatabaseDownloader
     * @param CacheItemPoolInterface $cache
     * @param EntityManagerInterface $entityManager
     * @param PackageDatabaseMirror $packageDatabaseMirror
     */
    public function __construct(
        PackageDatabaseDownloader $packageDatabaseDownloader,
        CacheItemPoolInterface $cache,
        EntityManagerInterface $entityManager,
        PackageDatabaseMirror $packageDatabaseMirror
    ) {
        parent::__construct();
        $this->cache = $cache;
        $this->packageDatabaseDownloader = $packageDatabaseDownloader;
        $this->entityManager = $entityManager;
        $this->packageDatabaseMirror = $packageDatabaseMirror;
    }

    protected function configure()
    {
        $this->setName('app:update:packages');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->lock('cron.lock', true);

        if (!$this->packageDatabaseMirror->hasUpdated()) {
            $this->printDebug('No updated packages available...', $output);

            return;
        }

        try {
            ini_set('memory_limit', '-1');

            foreach ($this->getContainer()->getParameter('app.packages.repositories') as $repoName => $arches) {
                foreach ($arches as $arch) {
                    $this->printDebug('Processing [' . $repoName . '] (' . $arch . ')', $output);
                    $repo = $this->getRepository($repoName, $arch);

                    $packageMTime = $this
                        ->entityManager
                        ->getRepository(Package::class)
                        ->getMaxMTimeByRepository($repo);

                    $this->printDebug("\tDownloading...", $output);
                    $packageDatabaseFile = $this->packageDatabaseDownloader
                        ->download($this->packageDatabaseMirror->getMirrorUrl(), $repo->getName(), $arch);

                    if (($repo->getMTime() && $packageDatabaseFile->getMTime() > $repo->getMTime()->getTimestamp())
                        || !$repo->getMTime()) {
                        $packages = new PackageDatabase(new PackageDatabaseReader($packageDatabaseFile));
                        if (!$output->isQuiet()) {
                            $progress = new ProgressBar($output, iterator_count($packages));
                            $progress->setFormatDefinition('minimal', "\tReading packages: %percent%%");
                            $progress->setFormat('minimal');
                            $progress->start();
                        }
                        $oldPackageNames = [];
                        /** @var DatabasePackage $package */
                        foreach ($packages as $package) {
                            if (isset($progress)) {
                                $progress->advance();
                            }
                            if (is_null($packageMTime)
                                || $package->getMTime()->getTimestamp() > $packageMTime->getTimestamp()) {
                                $this->updatePackage($repo, $package);
                            } else {
                                $oldPackageNames[] = $package->getName();
                            }
                        }
                        if (isset($progress)) {
                            $progress->finish();
                            $output->writeln('');
                        }

                        if (!is_null($packageMTime)) {
                            $this->printDebug("\tCleaning up obsolete packages...", $output);
                            $this->cleanupObsoletePackages($repo, $packageMTime, $oldPackageNames);
                        }

                        $repo->setMTime((new \DateTime())->setTimestamp($packageDatabaseFile->getMTime()));
                        $this->entityManager->persist($repo);
                    }
                }
            }
            $this->printDebug('Cleaning up obsolete repositories...', $output);
            $this->cleanupObsoleteRepositories($output);

            if ($this->updatedPackages) {
                $this->printDebug('Resolving package relations...', $output);
                $this->entityManager->flush();
                $this->resolveRelations();
            }

            $this->entityManager->flush();
            $this->packageDatabaseMirror->updateLastUpdate();
        } catch (\RuntimeException $e) {
            $this->printError(
                'UpdatePackages failed at ' . $e->getFile() . ' on line ' . $e->getLine() . ': ' . $e->getMessage(),
                $output
            );
        }

        $this->release();
    }

    /**
     * @param string $text
     * @param OutputInterface $output
     */
    private function printDebug(string $text, OutputInterface $output)
    {
        if (!$output->isQuiet()) {
            $output->writeln($text);
        }
    }

    /**
     * @param string $repoName
     * @param string $archName
     * @return Repository
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function getRepository(string $repoName, string $archName): Repository
    {
        $repository = $this
            ->entityManager
            ->getRepository(Repository::class)
            ->findByNameAndArchitecture($repoName, $archName);
        if (is_null($repository)) {
            $repository = new Repository($repoName, $archName);
            $repository->setTesting(preg_match('/(-|^)testing$/', $repoName) > 0);
            $this->entityManager->persist($repository);
            $this->entityManager->flush();
        }
        return $repository;
    }

    /**
     * @param Repository $repository
     * @param DatabasePackage $databasePackage
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @internal param Package $package
     */
    private function updatePackage(Repository $repository, DatabasePackage $databasePackage)
    {
        $package = $this
            ->entityManager
            ->getRepository(Package::class)
            ->findByRepositoryAndName($repository, $databasePackage->getName());
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
        $repoPackages = $this
            ->entityManager
            ->getRepository(Package::class)
            ->findByRepositoryOlderThan($repo, $packageMTime);

        /** @var Package $repoPackage */
        foreach ($repoPackages as $repoPackage) {
            if (!in_array($repoPackage->getName(), $allPackages)) {
                $this->entityManager->remove($repoPackage);
                $this->updatedPackages = true;
            }
        }
    }

    /**
     * @param OutputInterface $output
     */
    private function cleanupObsoleteRepositories(OutputInterface $output)
    {
        $repos = $this->entityManager->getRepository(Repository::class)->findAll();

        $configRepos = $this->getContainer()->getParameter('app.packages.repositories');
        foreach ($repos as $repo) {
            if (!isset($configRepos[$repo->getName()])
                || !in_array($repo->getArchitecture(), $configRepos[$repo->getName()])) {
                $this->printDebug(
                    "\tRemoving repository [" . $repo->getName() . "] (" . $repo->getArchitecture() . ")",
                    $output
                );
                $this->entityManager->remove($repo);
                $this->updatedPackages = true;
            }
        }
    }

    private function resolveRelations()
    {
        $this->entityManager->getRepository(AbstractRelation::class)->updateTargets();
    }

    /**
     * @param string $text
     * @param OutputInterface $output
     */
    private function printError(string $text, OutputInterface $output)
    {
        $output->writeln($text);
    }
}
