<?php

namespace App\Command\Update;

use App\Repository\PackageRepository;
use App\Repository\RepositoryRepository;
use App\Service\AppStreamDataFetcher;
use App\Service\AppStreamDataVersionObtainer;
use App\Service\KeywordProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UpdateAppStreamData extends Command
{
    use LockableTrait;

    /** @var array<string, array<string>> */
    private array $packageKeywords = [];

    public function __construct(
        /** @var string[] $appStreamDataReposToFetch */
        private readonly array $appStreamDataReposToFetch,
        private readonly AppStreamDataFetcher $appStreamDataFetcher,
        private readonly EntityManagerInterface $entityManager,
        private readonly PackageRepository $packageRepository,
        private readonly KeywordProcessor $keywordProcessor,
        private readonly RepositoryRepository $repositoryRepository
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

        foreach ($this->appStreamDataFetcher as $appStreamDto) {
            foreach($this->repositoryRepository->findBy(['testing' => false]) as $repository) {
                try {
                    $package = $this->packageRepository->getByName(
                        $repository->getName(),
                        'x86_64',
                        $appStreamDto->getPackageName()
                    );
                    $package->setKeywords($this->keywordProcessor->generatePackageKeywords($appStreamDto));
                    $this->entityManager->persist($package);
                } catch (NoResultException $e) {
                    // @todo: discuss what to do
                }
            }
        }

        $this->entityManager->flush();
        $this->release();


        return Command::SUCCESS;
    }
}
