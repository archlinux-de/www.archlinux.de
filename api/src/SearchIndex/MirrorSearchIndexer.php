<?php

namespace App\SearchIndex;

use App\Entity\Country;
use App\Entity\Mirror;

class MirrorSearchIndexer implements SearchIndexerInterface, SearchIndexConfigurationInterface
{
    public function __construct(private readonly string $environment)
    {
    }

    /**
     * @return array{'index': string, 'body': mixed[]}
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
        return ($this->environment === 'test' ? 'test-' : '') . 'mirror';
    }

    /**
     * @return list<mixed[]>
     */
    public function createBulkIndexStatement(object $object): array
    {
        assert($object instanceof Mirror);

        $paramsBody[] = ['index' => ['_index' => $this->getIndexName(), '_id' => $object->getUrl()]];
        $paramsBody[] = [
            'url' => $object->getUrl(),
            'country' => $object->getCountry() instanceof Country ? [
                'code' => $object->getCountry()->getCode(),
                'name' => $object->getCountry()->getName()
            ] : null,
            'score' => $object->getScore(),
            'lastSync' => $object->getLastSync()->format(DATE_W3C),
            'popularity' => $object->getPopularity()?->getPopularity(),
        ];

        return $paramsBody;
    }

    /**
     * @return list<mixed[]>
     */
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
