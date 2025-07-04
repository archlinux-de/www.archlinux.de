<?php

namespace App\Command\Index;

use App\Entity\Release;
use App\Repository\ReleaseRepository;
use App\SearchIndex\ReleaseSearchIndexer;
use App\SearchIndex\SearchIndexer;
use OpenSearch\Client;
use OpenSearch\Exception\NotFoundHttpException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IndexReleasesCommand extends Command
{
    use LockableTrait;

    public function __construct(
        private readonly Client $client,
        private readonly ReleaseRepository $releaseRepository,
        private readonly ReleaseSearchIndexer $releaseSearchIndexer
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('app:index:releases');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->lock('releases.lock');

        try {
            $this->client->indices()->delete(['index' => $this->releaseSearchIndexer->getIndexName()]);
        } catch (NotFoundHttpException) {
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

        return Command::SUCCESS;
    }
}
