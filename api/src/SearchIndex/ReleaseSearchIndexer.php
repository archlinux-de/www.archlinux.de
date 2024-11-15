<?php

namespace App\SearchIndex;

use App\Entity\Release;

class ReleaseSearchIndexer implements SearchIndexerInterface, SearchIndexConfigurationInterface
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
                        'version' => ['type' => 'text'],
                        'info' => ['type' => 'text'],
                        'kernelVersion' => ['type' => 'text'],
                        'releaseDate' => ['type' => 'date'],
                        'available' => ['type' => 'boolean']
                    ]
                ]
            ]
        ];
    }

    public function getIndexName(): string
    {
        return ($this->environment == 'test' ? 'test-' : '') . 'release';
    }

    /**
     * @return list<mixed[]>
     */
    public function createBulkIndexStatement(object $object): array
    {
        assert($object instanceof Release);

        $paramsBody[] = ['index' => ['_index' => $this->getIndexName(), '_id' => $object->getVersion()]];
        $paramsBody[] = [
            'version' => $object->getVersion(),
            'info' => strip_tags($object->getInfo()),
            'kernelVersion' => $object->getKernelVersion(),
            'releaseDate' => $object->getReleaseDate()->format(DATE_W3C),
            'available' => $object->isAvailable(),
        ];

        return $paramsBody;
    }

    /**
     * @return list<mixed[]>
     */
    public function createBulkDeleteStatement(object $object): array
    {
        assert($object instanceof Release);

        return [['delete' => ['_index' => $this->getIndexName(), '_id' => $object->getVersion()]]];
    }

    public function supportsIndexing(object $object): bool
    {
        return $object instanceof Release;
    }
}
