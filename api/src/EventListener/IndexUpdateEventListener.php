<?php

namespace App\EventListener;

use App\SearchIndex\SearchIndexer;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Elasticsearch\Client;

class IndexUpdateEventListener
{
    private array $bulkStatements = [];

    public function __construct(
        private Client $client,
        private SearchIndexer $searchIndexer,
        private string $environment
    ) {
    }

    public function postUpdate(LifecycleEventArgs $eventArgs): void
    {
        $this->postPersist($eventArgs);
    }

    public function postPersist(LifecycleEventArgs $eventArgs): void
    {
        $entity = $eventArgs->getObject();

        if ($this->searchIndexer->supportsIndexing($entity)) {
            $this->bulkStatements[] = $this->searchIndexer->createBulkIndexStatement($entity);
        }
    }

    public function preRemove(LifecycleEventArgs $eventArgs): void
    {
        $entity = $eventArgs->getObject();

        if ($this->searchIndexer->supportsIndexing($entity)) {
            $this->bulkStatements[] = $this->searchIndexer->createBulkDeleteStatement($entity);
        }
    }

    public function postFlush(): void
    {
        if ($this->bulkStatements) {
            foreach (array_chunk($this->bulkStatements, SearchIndexer::BULK_SIZE) as $bulkIndexChunk) {
                $this->client->bulk(
                    [
                        'body' => array_merge(...$bulkIndexChunk),
                        'refresh' => $this->environment != 'prod'
                    ]
                );
            }
            $this->bulkStatements = [];
        }
    }
}
