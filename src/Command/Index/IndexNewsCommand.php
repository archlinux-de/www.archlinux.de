<?php

namespace App\Command\Index;

use App\Entity\NewsItem;
use App\Repository\NewsItemRepository;
use App\SearchIndex\NewsSearchIndexer;
use App\SearchIndex\SearchIndexer;
use Elasticsearch\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IndexNewsCommand extends Command
{
    use LockableTrait;

    /** @var Client */
    private $client;

    /** @var NewsItemRepository */
    private $newsItemRepository;

    /** @var NewsSearchIndexer */
    private $newsSearchIndexer;

    /**
     * @param Client $client
     * @param NewsItemRepository $newsItemRepository
     * @param NewsSearchIndexer $newsSearchIndexer
     */
    public function __construct(
        Client $client,
        NewsItemRepository $newsItemRepository,
        NewsSearchIndexer $newsSearchIndexer
    ) {
        parent::__construct();
        $this->client = $client;
        $this->newsItemRepository = $newsItemRepository;
        $this->newsSearchIndexer = $newsSearchIndexer;
    }

    protected function configure(): void
    {
        $this->setName('app:index:news');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->lock('news.lock');

        if ($this->client->indices()->exists(['index' => $this->newsSearchIndexer->getIndexName()])) {
            $this->client->indices()->delete(['index' => $this->newsSearchIndexer->getIndexName()]);
        }

        $this->client->indices()->create($this->newsSearchIndexer->createIndexConfiguration());

        foreach (array_chunk($this->newsItemRepository->findAll(), SearchIndexer::BULK_SIZE) as $newsItems) {
            $paramsBody = [];
            /** @var NewsItem $newsItem */
            foreach ($newsItems as $newsItem) {
                $paramsBody = [...$paramsBody, ...$this->newsSearchIndexer->createBulkIndexStatement($newsItem)];
            }

            $this->client->bulk(['body' => $paramsBody]);
        }

        return Command::SUCCESS;
    }
}
