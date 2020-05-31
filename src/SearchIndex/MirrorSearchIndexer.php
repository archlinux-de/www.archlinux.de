<?php

namespace App\SearchIndex;

use App\Entity\Mirror;

class MirrorSearchIndexer implements SearchIndexerInterface, SearchIndexConfigurationInterface
{
    /**
     * @return array<mixed>
     */
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
        return 'mirror';
    }

    /**
     * @param Mirror $object
     * @return array<mixed>>
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
     * @return array<mixed>>
     */
    public function createBulkDeleteStatement(object $object): array
    {
        return [['delete' => ['_index' => $this->getIndexName(), '_id' => $object->getUrl()]]];
    }

    /**
     * @param Mirror $object
     * @return bool
     */
    public function supportsIndexing(object $object): bool
    {
        return $object instanceof Mirror;
    }
}
