<?php

namespace App\SearchIndex;

use App\Entity\Mirror;

class MirrorSearchIndexer implements SearchIndexerInterface, SearchIndexConfigurationInterface
{
    public function __construct(private readonly string $environment)
    {
    }

    public function createIndexConfiguration(): array
    {
        return [
            'index' => $this->getIndexName(),
            'body' => [
                'mappings' => [
                    'properties' => [
                        'url' => ['type' => 'text'],
                        'country' => [
                            'type' => 'object',
                            'properties' => [
                                'code' => ['type' => 'keyword'],
                                'name' => ['type' => 'text']
                            ]
                        ],
                        'score' => ['type' => 'float'],
                        'lastSync' => ['type' => 'date'],
                        'popularity' => ['type' => 'float'],
                    ]
                ]
            ]
        ];
    }

    public function getIndexName(): string
    {
        return ($this->environment == 'test' ? 'test-' : '') . 'mirror';
    }

    public function createBulkIndexStatement(object $object): array
    {
        assert($object instanceof Mirror);

        $paramsBody[] = ['index' => ['_index' => $this->getIndexName(), '_id' => $object->getUrl()]];
        $paramsBody[] = [
            'url' => $object->getUrl(),
            'country' => $object->getCountry() !== null ? [
                'code' => $object->getCountry()->getCode(),
                'name' => $object->getCountry()->getName()
            ] : null,
            'score' => $object->getScore(),
            'lastSync' => $object->getLastSync()->format(DATE_W3C),
            'popularity' => $object->getPopularity()?->getPopularity(),
        ];

        return $paramsBody;
    }

    public function createBulkDeleteStatement(object $object): array
    {
        assert($object instanceof Mirror);

        return [['delete' => ['_index' => $this->getIndexName(), '_id' => $object->getUrl()]]];
    }

    public function supportsIndexing(object $object): bool
    {
        return $object instanceof Mirror;
    }
}
