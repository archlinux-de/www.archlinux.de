<?php

namespace App\SearchIndex;

use App\Entity\Release;

class ReleaseSearchIndexer implements SearchIndexerInterface, SearchIndexConfigurationInterface
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
                        'version' => ['type' => 'text'],
                        'info' => ['type' => 'text'],
                        'kernelVersion' => ['type' => 'text'],
                        'releaseDate' => ['type' => 'date']
                    ]
                ]
            ]
        ];
    }

    public function getIndexName(): string
    {
        return 'release';
    }

    /**
     * @param Release $object
     * @return array<mixed>>
     */
    public function createBulkIndexStatement(object $object): array
    {
        $paramsBody[] = ['index' => ['_index' => $this->getIndexName(), '_id' => $object->getVersion()]];
        $paramsBody[] = [
            'version' => $object->getVersion(),
            'info' => strip_tags($object->getInfo()),
            'kernelVersion' => $object->getKernelVersion(),
            'releaseDate' => $object->getReleaseDate()->format(DATE_W3C)
        ];

        return $paramsBody;
    }

    /**
     * @param Release $object
     * @return array<mixed>>
     */
    public function createBulkDeleteStatement(object $object): array
    {
        return [['delete' => ['_index' => $this->getIndexName(), '_id' => $object->getVersion()]]];
    }

    /**
     * @param Release $object
     * @return bool
     */
    public function supportsIndexing(object $object): bool
    {
        return $object instanceof Release;
    }
}
