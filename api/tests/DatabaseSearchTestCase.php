<?php

namespace App\Tests;

use App\SearchIndex\MirrorSearchIndexer;
use App\SearchIndex\NewsSearchIndexer;
use App\SearchIndex\PackageSearchIndexer;
use App\SearchIndex\ReleaseSearchIndexer;
use App\SearchIndex\SearchIndexConfigurationInterface;
use Elasticsearch\Client;
use SymfonyDatabaseTest\DatabaseTestCase;

abstract class DatabaseSearchTestCase extends DatabaseTestCase
{
    /**
     * @var SearchIndexConfigurationInterface[]
     */
    private array $searchIndexers = [];

    public function setUp(): void
    {
        parent::setUp();

        $this->searchIndexers = $this->createSearchIndexers();
        foreach ($this->searchIndexers as $searchIndexer) {
            $this->createSearchIndex($searchIndexer);
        }
    }

    /**
     * @return SearchIndexConfigurationInterface[]
     */
    private function createSearchIndexers(): array
    {
        $environment = $this->getClient()->getKernel()->getEnvironment();
        return [
            new MirrorSearchIndexer($environment),
            new NewsSearchIndexer($environment),
            new PackageSearchIndexer($environment),
            new ReleaseSearchIndexer($environment)
        ];
    }

    private function createSearchIndex(SearchIndexConfigurationInterface $searchIndexer): void
    {
        $elasticsearchClient = $this->getElasticsearchClient();

        if ($elasticsearchClient->indices()->exists(['index' => $searchIndexer->getIndexName()])) {
            $elasticsearchClient->indices()->delete(['index' => $searchIndexer->getIndexName()]);
        }
        $elasticsearchClient->indices()->create($searchIndexer->createIndexConfiguration());
    }

    private function getElasticsearchClient(): Client
    {
        $container = static::getClient()->getContainer();
        static::assertNotNull($container);
        /** @var Client $client */
        $client = $container->get(Client::class);
        static::assertNotNull($client);
        return $client;
    }

    public function tearDown(): void
    {
        foreach ($this->searchIndexers as $searchIndexer) {
            $this->getElasticsearchClient()->indices()->delete(['index' => $searchIndexer->getIndexName()]);
        }

        parent::tearDown();
    }
}
