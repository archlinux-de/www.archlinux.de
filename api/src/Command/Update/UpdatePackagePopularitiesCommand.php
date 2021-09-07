<?php

namespace App\Command\Update;

use App\Entity\Packages\Popularity;
use App\Exception\ValidationException;
use App\Repository\PackageRepository;
use App\Service\PackagePopularityFetcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UpdatePackagePopularitiesCommand extends Command
{
    use LockableTrait;

    private array $packagePopularities = [];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private PackagePopularityFetcher $packagePopularityFetcher,
        private PackageRepository $packageRepository,
        private ValidatorInterface $validator
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

        /**
         * @var string $name
         * @var Popularity $popularity
         */
        foreach ($this->packagePopularityFetcher as $name => $popularity) {
            $errors = $this->validator->validate($popularity);
            if ($errors->count() > 0) {
                throw new ValidationException($errors);
            }
            $this->packagePopularities[$name] = $popularity;
        }

        foreach ($this->packageRepository->findStable() as $package) {
            $package->setPopularity($this->packagePopularities[$package->getName()] ?? null);
        }

        $this->entityManager->flush();
        $this->release();

        return Command::SUCCESS;
    }
}
