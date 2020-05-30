<?php

namespace App\EventListener;

use App\SearchIndex\SearchIndexer;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Elasticsearch\Client;

class IndexUpdateEventListener
{
    /** @var Client */
    private $client;

    /** @var SearchIndexer */
    private $searchIndexer;

    /** @var array<mixed> */
    private $bulkStatements = [];

    /**
     * @param Client $client
     * @param SearchIndexer $searchIndexer
     */
    public function __construct(Client $client, SearchIndexer $searchIndexer)
    {
        $this->client = $client;
        $this->searchIndexer = $searchIndexer;
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

    /**
     * @param PostFlushEventArgs $eventArgs
     */
    public function postFlush(PostFlushEventArgs $eventArgs): void
    {
        if ($this->bulkStatements) {
            foreach (array_chunk($this->bulkStatements, SearchIndexer::BULK_SIZE) as $bulkIndexChunk) {
                $this->client->bulk(['body' => array_merge(...$bulkIndexChunk)]);
            }
            $this->bulkStatements = [];
        }
    }
}
