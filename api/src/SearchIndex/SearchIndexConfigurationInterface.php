<?php

namespace App\SearchIndex;

interface SearchIndexConfigurationInterface
{
    public function createIndexConfiguration(): array;
    public function getIndexName(): string;
}
