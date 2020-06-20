<?php

namespace App\SearchIndex;

class SearchIndexer implements SearchIndexerInterface
{
    public const BULK_SIZE = 1000;

    /** @var SearchIndexerInterface[] */
    private $searchIndexers = [];

    /**
     * @param SearchIndexerInterface[] $searchIndexers
     */
    public function __construct(array $searchIndexers)
    {
        $this->searchIndexers = $searchIndexers;
    }

    /**
     * @param object $object
     * @return array
     */
    public function createBulkIndexStatement(object $object): array
    {
        foreach ($this->searchIndexers as $searchIndexer) {
            if ($searchIndexer->supportsIndexing($object)) {
                return $searchIndexer->createBulkIndexStatement($object);
            }
        }
        return [];
    }

    /**
     * @param object $object
     * @return array
     */
    public function createBulkDeleteStatement(object $object): array
    {
        foreach ($this->searchIndexers as $searchIndexer) {
            if ($searchIndexer->supportsIndexing($object)) {
                return $searchIndexer->createBulkDeleteStatement($object);
            }
        }
        return [];
    }

    /**
     * @param object $object
     * @return bool
     */
    public function supportsIndexing(object $object): bool
    {
        foreach ($this->searchIndexers as $searchIndexer) {
            if ($searchIndexer->supportsIndexing($object)) {
                return true;
            }
        }
        return false;
    }
}
