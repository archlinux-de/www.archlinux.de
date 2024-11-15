<?php

namespace App\SearchIndex;

interface SearchIndexConfigurationInterface
{
    /**
     * @return array{'index': string, 'body': mixed[]}
     */
    public function createIndexConfiguration(): array;

    public function getIndexName(): string;
}
