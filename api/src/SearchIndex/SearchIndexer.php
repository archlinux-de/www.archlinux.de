<?php

namespace App\SearchIndex;

class SearchIndexer implements SearchIndexerInterface
{
    public const int BULK_SIZE = 1000;

    /**
     * @param SearchIndexerInterface[] $searchIndexers
     */
    public function __construct(private readonly array $searchIndexers)
    {
    }

    public function createBulkIndexStatement(object $object): array
    {
        foreach ($this->searchIndexers as $searchIndexer) {
            if ($searchIndexer->supportsIndexing($object)) {
                return $searchIndexer->createBulkIndexStatement($object);
            }
        }
        return [];
    }

    public function createBulkDeleteStatement(object $object): array
    {
        foreach ($this->searchIndexers as $searchIndexer) {
            if ($searchIndexer->supportsIndexing($object)) {
                return $searchIndexer->createBulkDeleteStatement($object);
            }
        }
        return [];
    }

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
