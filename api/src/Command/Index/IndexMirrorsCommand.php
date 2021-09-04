<?php

namespace App\Command\Index;

use App\Entity\Mirror;
use App\Repository\MirrorRepository;
use App\SearchIndex\MirrorSearchIndexer;
use App\SearchIndex\SearchIndexer;
use Elasticsearch\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IndexMirrorsCommand extends Command
{
    use LockableTrait;

    public function __construct(
        private Client $client,
        private MirrorRepository $mirrorRepository,
        private MirrorSearchIndexer $mirrorSearchIndexer
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('app:index:mirrors');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->lock('mirrors.lock');

        if ($this->client->indices()->exists(['index' => $this->mirrorSearchIndexer->getIndexName()])) {
            $this->client->indices()->delete(['index' => $this->mirrorSearchIndexer->getIndexName()]);
        }

        $this->client->indices()->create($this->mirrorSearchIndexer->createIndexConfiguration());

        foreach (array_chunk($this->mirrorRepository->findAll(), SearchIndexer::BULK_SIZE) as $mirrors) {
            $paramsBody = [];
            /** @var Mirror $mirror */
            foreach ($mirrors as $mirror) {
                $paramsBody = [...$paramsBody, ...$this->mirrorSearchIndexer->createBulkIndexStatement($mirror)];
            }

            $this->client->bulk(['body' => $paramsBody]);
        }

        return Command::SUCCESS;
    }
}
