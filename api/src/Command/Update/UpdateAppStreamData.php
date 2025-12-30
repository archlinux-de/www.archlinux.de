<?php

namespace App\Command\Update;

use App\Repository\PackageRepository;
use App\Service\AppStreamDataFetcher;
use App\Service\AppStreamDataVersionObtainer;
use App\Service\KeywordsCleaner;
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

    /** @var array<string, array<string>> */
    private array $packageKeywords = [];

    public function __construct(
        private readonly string $appStreamDataBaseUrl,
        private readonly string $appStreamDataFile,
        /** @var string[] $appStreamDataReposToFetch */
        private readonly array $appStreamDataReposToFetch,
        private readonly EntityManagerInterface $entityManager,
        private readonly PackageRepository $packageRepository,
        private readonly ValidatorInterface $validator,
        private readonly AppStreamDataVersionObtainer $appStreamDataVersionObtainer,
        private readonly KeywordsCleaner $keywordCleaner
    ) {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this
            ->setName('app:update:appstream-data')
            ->setDescription('
            Update appstream data for packages defined in app.yaml "app.packages.appStreamDataReposToFetch".');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->lock('appstream.lock');
        ini_set('memory_limit', '8G');

        // AppStreamDataFetcher yields pkgname => keywords
        foreach ($this->appStreamDataReposToFetch as $repoToFetchFor) {
            $dataFetcher = new AppStreamDataFetcher(
                $this->appStreamDataBaseUrl,
                $this->appStreamDataFile,
                $repoToFetchFor,
                $this->appStreamDataVersionObtainer,
                $this->keywordCleaner
            );

            foreach ($dataFetcher as $name => $keywords) {
                $errors = $this->validator->validate($keywords);
                if ($errors->count() > 0) {
                    throw new ValidationFailedException($keywords, $errors);
                }
                $this->packageKeywords[$name] = $keywords;
            }

            foreach ($this->packageRepository->findStable() as $package) {
                if (isset($this->packageKeywords[$package->getName()])) {
                    $package->setKeywords($this->packageKeywords[$package->getName()]);
                    $this->entityManager->persist($package);
                }
            }

            $this->entityManager->flush();
            $this->release();
        }

        return Command::SUCCESS;
    }
}
