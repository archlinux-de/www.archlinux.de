<?php

namespace App\SearchIndex;

use App\Entity\Packages\Package;
use App\Entity\Packages\Relations\Provision;
use App\Entity\Packages\Relations\Replacement;

class PackageSearchIndexer implements SearchIndexerInterface, SearchIndexConfigurationInterface
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
                'settings' => ['max_result_window' => 20000],
                'mappings' => [
                    'properties' => [
                        'name' => ['type' => 'text'],
                        'base' => ['type' => 'text'],
                        'description' => ['type' => 'text'],
                        'url' => ['type' => 'text'],
                        'groups' => ['type' => 'text'],
                        'buildDate' => ['type' => 'date'],
                        'replacements' => ['type' => 'text'],
                        'provisions' => ['type' => 'text'],
                        'repository' => [
                            'type' => 'object',
                            'properties' => [
                                'name' => ['type' => 'keyword'],
                                'architecture' => ['type' => 'keyword'],
                                'testing' => ['type' => 'boolean']
                            ]
                        ],
                        'popularity' => ['type' => 'float'],
                        'files' => ['type' => 'text']
                    ],
                    '_source' => ['excludes' => ['files']]
                ]
            ]
        ];
    }

    public function getIndexName(): string
    {
        return ($this->environment == 'test' ? 'test-' : '') . 'package';
    }

    /**
     * @return list<mixed[]>
     */
    public function createBulkIndexStatement(object $object): array
    {
        assert($object instanceof Package);

        $paramsBody[] = ['index' => ['_index' => $this->getIndexName(), '_id' => $object->getId()]];
        $paramsBody[] = [
            'name' => $object->getName(),
            'base' => $object->getBase(),
            'description' => $object->getDescription(),
            'url' => $object->getUrl(),
            'groups' => $object->getGroups(),
            'buildDate' => $object->getBuildDate()?->format(DATE_W3C),
            'replacements' => $object->getReplacements()->map(
                fn(Replacement $replacement): string => $replacement->getTargetName()
            )->toArray(),
            'provisions' => $object->getProvisions()->map(
                fn(Provision $provision): string => $provision->getTargetName()
            )->toArray(),
            'repository' => [
                'name' => $object->getRepository()->getName(),
                'architecture' => $object->getRepository()->getArchitecture(),
                'testing' => $object->getRepository()->isTesting()
            ],
            'popularity' => $object->getPopularity()?->getPopularity(),
            'files' => [...$object->getFiles()]
        ];

        return $paramsBody;
    }

    /**
     * @return list<mixed[]>
     */
    public function createBulkDeleteStatement(object $object): array
    {
        assert($object instanceof Package);

        return [['delete' => ['_index' => $this->getIndexName(), '_id' => $object->getId()]]];
    }

    public function supportsIndexing(object $object): bool
    {
        return $object instanceof Package;
    }
}
