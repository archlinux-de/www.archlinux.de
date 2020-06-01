<?php

namespace App\Command\Update;

use App\Repository\PackageRepository;
use App\Service\PackagePopularityFetcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdatePackagePopularitiesCommand extends Command
{
    use LockableTrait;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var PackagePopularityFetcher */
    private $packagePopularityFetcher;

    /** @var PackageRepository */
    private $packageRepository;

    /** @var array<string,float> */
    private $packagePopularities = [];

    /**
     * @param EntityManagerInterface $entityManager
     * @param PackagePopularityFetcher $packagePopularityFetcher
     * @param PackageRepository $packageRepository
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        PackagePopularityFetcher $packagePopularityFetcher,
        PackageRepository $packageRepository
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->packagePopularityFetcher = $packagePopularityFetcher;
        $this->packageRepository = $packageRepository;
    }

    protected function configure(): void
    {
        $this->setName('app:update:package-popularities');
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

        foreach ($this->packagePopularityFetcher as $name => $popularity) {
            $this->packagePopularities[$name] = $popularity;
        }

        foreach ($this->packageRepository->findStable() as $package) {
            $package->setPopularity($this->packagePopularities[$package->getName()] ?? 0);
        }

        $this->entityManager->flush();
        $this->release();

        return Command::SUCCESS;
    }
}
