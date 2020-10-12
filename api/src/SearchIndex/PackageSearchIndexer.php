<?php

namespace App\SearchIndex;

use App\Entity\Packages\Package;
use App\Entity\Packages\Relations\Provision;
use App\Entity\Packages\Relations\Replacement;

class PackageSearchIndexer implements SearchIndexerInterface, SearchIndexConfigurationInterface
{
    /** @var string */
    private $environment;

    /**
     * @param string $environment
     */
    public function __construct(string $environment)
    {
        $this->environment = $environment;
    }

    /**
     * @return array
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
     * @param Package $object
     * @return array>
     */
    public function createBulkIndexStatement(object $object): array
    {
        $paramsBody[] = ['index' => ['_index' => $this->getIndexName(), '_id' => $object->getId()]];
        $paramsBody[] = [
            'name' => $object->getName(),
            'base' => $object->getBase(),
            'description' => $object->getDescription(),
            'url' => $object->getUrl(),
            'groups' => $object->getGroups(),
            'buildDate' => $object->getBuildDate() !== null ? $object->getBuildDate()->format(DATE_W3C) : null,
            'replacements' => $object->getReplacements()->map(
                fn(Replacement $replacement) => $replacement->getTargetName()
            )->toArray(),
            'provisions' => $object->getProvisions()->map(
                fn(Provision $provision) => $provision->getTargetName()
            )->toArray(),
            'repository' => [
                'name' => $object->getRepository()->getName(),
                'architecture' => $object->getRepository()->getArchitecture(),
                'testing' => $object->getRepository()->isTesting()
            ],
            'popularity' => $object->getPopularity(),
            'files' => [...$object->getFiles()]
        ];

        return $paramsBody;
    }

    /**
     * @param Package $object
     * @return array>
     */
    public function createBulkDeleteStatement(object $object): array
    {
        return [['delete' => ['_index' => $this->getIndexName(), '_id' => $object->getId()]]];
    }

    /**
     * @param Package $object
     * @return bool
     */
    public function supportsIndexing(object $object): bool
    {
        return $object instanceof Package;
    }
}
