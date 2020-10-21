<?php

namespace App\EventListener;

use App\SearchIndex\SearchIndexer;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Elasticsearch\Client;

class IndexUpdateEventListener
{
    /** @var Client */
    private $client;

    /** @var SearchIndexer */
    private $searchIndexer;

    /** @var array */
    private $bulkStatements = [];

    /** @var string */
    private $environment;

    /**
     * @param Client $client
     * @param SearchIndexer $searchIndexer
     * @param string $environment
     */
    public function __construct(Client $client, SearchIndexer $searchIndexer, string $environment)
    {
        $this->client = $client;
        $this->searchIndexer = $searchIndexer;
        $this->environment = $environment;
    }

    /**
     * @param LifecycleEventArgs $eventArgs
     */
    public function postUpdate(LifecycleEventArgs $eventArgs): void
    {
        $this->postPersist($eventArgs);
    }

    /**
     * @param LifecycleEventArgs $eventArgs
     */
    public function postPersist(LifecycleEventArgs $eventArgs): void
    {
        $entity = $eventArgs->getObject();

        if ($this->searchIndexer->supportsIndexing($entity)) {
            $this->bulkStatements[] = $this->searchIndexer->createBulkIndexStatement($entity);
        }
    }

    /**
     * @param LifecycleEventArgs $eventArgs
     */
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
