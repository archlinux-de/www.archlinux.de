<?php

namespace App\SearchIndex;

interface SearchIndexConfigurationInterface
{
    /**
     * @return array
     */
    public function createIndexConfiguration(): array;

    /**
     * @return string
     */
    public function getIndexName(): string;
}
