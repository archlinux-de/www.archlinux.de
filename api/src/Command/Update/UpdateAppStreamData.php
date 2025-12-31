<?php

namespace App\Command\Update;

use App\Repository\PackageRepository;
use App\Service\AppStreamDataFetcher;
use App\Service\KeywordProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateAppStreamData extends Command
{
    use LockableTrait;

    public function __construct(
        private readonly AppStreamDataFetcher $appStreamDataFetcher,
        private readonly EntityManagerInterface $entityManager,
        private readonly PackageRepository $packageRepository,
        private readonly KeywordProcessor $keywordProcessor,
        private readonly LoggerInterface $logger,
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
            $package = $this->packageRepository->findOneByName($appStreamDto->getPackageName());

            if ($package === null) {
                $this->logger->info(sprintf('Package with name %s not found in database', $appStreamDto->getPackageName()));
                continue;
            }

            $package->setKeywords($this->keywordProcessor->generatePackageKeywords($appStreamDto));
            $this->entityManager->persist($package);
        }

        $this->entityManager->flush();
        $this->release();


        return Command::SUCCESS;
    }
}
