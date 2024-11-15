<?php

namespace App\SearchIndex;

interface SearchIndexerInterface
{
    /**
     * @return list<mixed[]>
     */
    public function createBulkIndexStatement(object $object): array;

    /**
     * @return list<mixed[]>
     */
    public function createBulkDeleteStatement(object $object): array;

    public function supportsIndexing(object $object): bool;
}
