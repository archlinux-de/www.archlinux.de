<?php

namespace App\Command\Index;

use App\Entity\Release;
use App\Repository\ReleaseRepository;
use App\SearchIndex\ReleaseSearchIndexer;
use App\SearchIndex\SearchIndexer;
use Elasticsearch\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IndexReleasesCommand extends Command
{
    use LockableTrait;

    /** @var Client */
    private $client;

    /** @var ReleaseRepository */
    private $releaseRepository;

    /** @var ReleaseSearchIndexer */
    private $releaseSearchIndexer;

    /**
     * @param Client $client
     * @param ReleaseRepository $releaseRepository
     * @param ReleaseSearchIndexer $releaseSearchIndexer
     */
    public function __construct(
        Client $client,
        ReleaseRepository $releaseRepository,
        ReleaseSearchIndexer $releaseSearchIndexer
    ) {
        parent::__construct();
        $this->client = $client;
        $this->releaseRepository = $releaseRepository;
        $this->releaseSearchIndexer = $releaseSearchIndexer;
    }

    protected function configure(): void
    {
        $this->setName('app:index:releases');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->lock('releases.lock');

        if ($this->client->indices()->exists(['index' => $this->releaseSearchIndexer->getIndexName()])) {
            $this->client->indices()->delete(['index' => $this->releaseSearchIndexer->getIndexName()]);
        }

        $this->client->indices()->create($this->releaseSearchIndexer->createIndexConfiguration());

        foreach (array_chunk($this->releaseRepository->findAll(), SearchIndexer::BULK_SIZE) as $releases) {
            $paramsBody = [];
            /** @var Release $release */
            foreach ($releases as $release) {
                $paramsBody = [...$paramsBody, ...$this->releaseSearchIndexer->createBulkIndexStatement($release)];
            }

            $this->client->bulk(['body' => $paramsBody]);
        }

        return 0;
    }
}
