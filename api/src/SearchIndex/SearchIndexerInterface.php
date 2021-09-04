<?php

namespace App\SearchIndex;

interface SearchIndexerInterface
{
    public function createBulkIndexStatement(object $object): array;
    public function createBulkDeleteStatement(object $object): array;
    public function supportsIndexing(object $object): bool;
}
