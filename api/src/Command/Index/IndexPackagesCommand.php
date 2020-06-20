<?php

namespace App\Command\Index;

use App\Entity\Packages\Package;
use App\Repository\PackageRepository;
use App\SearchIndex\PackageSearchIndexer;
use App\SearchIndex\SearchIndexer;
use Elasticsearch\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IndexPackagesCommand extends Command
{
    use LockableTrait;

    /** @var Client */
    private $client;

    /** @var PackageRepository */
    private $packageRepository;

    /** @var PackageSearchIndexer */
    private $packageSearchIndexer;

    /**
     * @param Client $client
     * @param PackageRepository $packageRepository
     * @param PackageSearchIndexer $packageSearchIndexer
     */
    public function __construct(
        Client $client,
        PackageRepository $packageRepository,
        PackageSearchIndexer $packageSearchIndexer
    ) {
        parent::__construct();
        $this->client = $client;
        $this->packageRepository = $packageRepository;
        $this->packageSearchIndexer = $packageSearchIndexer;
    }

    protected function configure(): void
    {
        $this->setName('app:index:packages');
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

        if ($this->client->indices()->exists(['index' => $this->packageSearchIndexer->getIndexName()])) {
            $this->client->indices()->delete(['index' => $this->packageSearchIndexer->getIndexName()]);
        }

        $this->client->indices()->create($this->packageSearchIndexer->createIndexConfiguration());

        foreach (array_chunk($this->packageRepository->findAll(), SearchIndexer::BULK_SIZE) as $packages) {
            $paramsBody = [];
            /** @var Package $package */
            foreach ($packages as $package) {
                $paramsBody = [...$paramsBody, ...$this->packageSearchIndexer->createBulkIndexStatement($package)];
            }

            $this->client->bulk(['body' => $paramsBody]);
        }

        return Command::SUCCESS;
    }
}
