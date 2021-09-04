<?php

namespace App\SearchIndex;

use App\Entity\Mirror;

class MirrorSearchIndexer implements SearchIndexerInterface, SearchIndexConfigurationInterface
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
                        'url' => ['type' => 'text'],
                        'country' => [
                            'type' => 'object',
                            'properties' => [
                                'code' => ['type' => 'keyword'],
                                'name' => ['type' => 'text']
                            ]
                        ],
                        'protocol' => ['type' => 'keyword'],
                        'score' => ['type' => 'float'],
                        'lastSync' => ['type' => 'date']
                    ]
                ]
            ]
        ];
    }

    public function getIndexName(): string
    {
        return ($this->environment == 'test' ? 'test-' : '') . 'mirror';
    }

    /**
     * @param Mirror $object
     */
    public function createBulkIndexStatement(object $object): array
    {
        $paramsBody[] = ['index' => ['_index' => $this->getIndexName(), '_id' => $object->getUrl()]];
        $paramsBody[] = [
            'url' => $object->getUrl(),
            'country' => $object->getCountry() !== null ? [
                'code' => $object->getCountry()->getCode(),
                'name' => $object->getCountry()->getName()
            ] : null,
            'protocol' => $object->getProtocol(),
            'score' => $object->getScore(),
            'lastSync' => $object->getLastSync()->format(DATE_W3C)
        ];

        return $paramsBody;
    }

    /**
     * @param Mirror $object
     */
    public function createBulkDeleteStatement(object $object): array
    {
        return [['delete' => ['_index' => $this->getIndexName(), '_id' => $object->getUrl()]]];
    }

    /**
     * @param Mirror $object
     */
    public function supportsIndexing(object $object): bool
    {
        return $object instanceof Mirror;
    }
}
