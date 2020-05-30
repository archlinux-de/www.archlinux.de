<?php

namespace App\SearchIndex;

interface SearchIndexerInterface
{
    /**
     * @param object $object
     * @return array<mixed>
     */
    public function createBulkIndexStatement(object $object): array;

    /**
     * @param object $object
     * @return array<mixed>
     */
    public function createBulkDeleteStatement(object $object): array;

    /**
     * @param object $object
     * @return bool
     */
    public function supportsIndexing(object $object): bool;
}
