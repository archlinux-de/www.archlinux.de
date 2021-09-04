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

    private array $packagePopularities = [];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private PackagePopularityFetcher $packagePopularityFetcher,
        private PackageRepository $packageRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('app:update:package-popularities');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->lock('packages.lock');
        ini_set('memory_limit', '4G');

        foreach ($this->packagePopularityFetcher as $name => $popularity) {
            if ($popularity < 0 || $popularity > 100) {
                throw new \RuntimeException(sprintf('Invalid popularity of %.2f%% for "%s"', $popularity, $name));
            }
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
