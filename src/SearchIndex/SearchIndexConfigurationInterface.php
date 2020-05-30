<?php

namespace App\SearchIndex;

interface SearchIndexConfigurationInterface
{
    /**
     * @return array<mixed>
     */
    public function createIndexConfiguration(): array;

    /**
     * @return string
     */
    public function getIndexName(): string;
}
