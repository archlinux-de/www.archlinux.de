<?php

namespace App\Command\Update;

use App\Repository\PackageRepository;
use App\Service\AppStreamDataFetcher;
use App\Service\AppStreamDataVersionObtainer;
use App\Service\KeywordsCleaner;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
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

            foreach ($dataFetcher as $appStreamDto) {
                try {
                    $package = $this->packageRepository->getByName(
                        $repoToFetchFor,
                        'x86_64',
                        $appStreamDto->getPackageName()
                    );
                    // todo: keywords at package should be description, category and keywords
                    // from appStreamDto; need function to generate -> refactor KeywordsCleaner
                    $package->setKeywords($appStreamDto->getKeywords());
                    $this->entityManager->persist($package);
                } catch (NoResultException $e) {
                    // @todo: discuss what to do
                }
            }

            $this->entityManager->flush();
            $this->release();
        }

        return Command::SUCCESS;
    }
}
