<?php

namespace App\Command\Update;

use App\Entity\Packages\Metadata;
use App\Repository\PackageRepository;
use App\Service\AppStreamDataFetcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UpdateAppStreamData extends Command
{
    use LockableTrait;

    /**
     * @var array<string, Metadata>
     */
    private array $packageMetaData = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AppStreamDataFetcher $appStreamDataFetcher,
        private readonly PackageRepository $packageRepository,
        private readonly ValidatorInterface $validator
    ) {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->setName('app:update:appstream-data');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->lock('appstream.lock');
        ini_set('memory_limit', '8G');

        foreach ($this->appStreamDataFetcher as $name => $metaData) {
            $errors = $this->validator->validate($metaData);
            if ($errors->count() > 0) {
                throw new ValidationFailedException($metaData, $errors);
            }
            $this->packageMetaData[$name] = $metaData;
        }

        foreach ($this->packageRepository->findStable() as $package) {
            $package->setMetaData($this->packagePopularities[$package->getName()] ?? null);
        }

        $this->entityManager->flush();
        $this->release();

        return Command::SUCCESS;
    }

}
