<?php

namespace App\SearchIndex;

use App\Entity\NewsItem;

class NewsSearchIndexer implements SearchIndexerInterface, SearchIndexConfigurationInterface
{
    public function __construct(private string $environment)
    {
    }

    public function createIndexConfiguration(): array
    {
        return [
            'index' => $this->getIndexName(),
            'body' => [
                'mappings' => [
                    'properties' => [
                        'title' => ['type' => 'text', 'analyzer' => 'german'],
                        'description' => ['type' => 'text', 'analyzer' => 'german'],
                        'author' => ['type' => 'text'],
                        'lastModified' => ['type' => 'date']
                    ]
                ]
            ]
        ];
    }

    public function getIndexName(): string
    {
        return ($this->environment == 'test' ? 'test-' : '') . 'news_item';
    }

    /**
     * @param NewsItem $object
     */
    public function createBulkIndexStatement(object $object): array
    {
        $paramsBody[] = ['index' => ['_index' => $this->getIndexName(), '_id' => $object->getId()]];
        $paramsBody[] = [
            'title' => $object->getTitle(),
            'description' => strip_tags($object->getDescription()),
            'author' => $object->getAuthor()->getName(),
            'lastModified' => $object->getLastModified()->format(DATE_W3C)
        ];

        return $paramsBody;
    }

    /**
     * @param NewsItem $object
     */
    public function createBulkDeleteStatement(object $object): array
    {
        return [['delete' => ['_index' => $this->getIndexName(), '_id' => $object->getId()]]];
    }

    /**
     * @param NewsItem $object
     */
    public function supportsIndexing(object $object): bool
    {
        return $object instanceof NewsItem;
    }
}
