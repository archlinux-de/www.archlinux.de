<?php

namespace App\Command\Index;

use App\Entity\NewsItem;
use App\Repository\NewsItemRepository;
use App\SearchIndex\NewsSearchIndexer;
use App\SearchIndex\SearchIndexer;
use OpenSearch\Client;
use OpenSearch\Exception\NotFoundHttpException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IndexNewsCommand extends Command
{
    use LockableTrait;

    public function __construct(
        private readonly Client $client,
        private readonly NewsItemRepository $newsItemRepository,
        private readonly NewsSearchIndexer $newsSearchIndexer
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('app:index:news');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->lock('news.lock');

        try {
            $this->client->indices()->delete(['index' => $this->newsSearchIndexer->getIndexName()]);
        } catch (NotFoundHttpException) {
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
