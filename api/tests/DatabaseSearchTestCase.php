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
    public function setUp(): void
    {
        parent::setUp();

        $this->createSearchIndex(new MirrorSearchIndexer());
        $this->createSearchIndex(new NewsSearchIndexer());
        $this->createSearchIndex(new PackageSearchIndexer());
        $this->createSearchIndex(new ReleaseSearchIndexer());
    }

    /**
     * @param SearchIndexConfigurationInterface $searchIndexer
     */
    private function createSearchIndex(SearchIndexConfigurationInterface $searchIndexer): void
    {
        $elasticsearchClient = $this->getElasticsearchClient();

        if ($elasticsearchClient->indices()->exists(['index' => $searchIndexer->getIndexName()])) {
            $elasticsearchClient->indices()->delete(['index' => $searchIndexer->getIndexName()]);
        }
        $elasticsearchClient->indices()->create($searchIndexer->createIndexConfiguration());
    }

    /**
     * @return Client
     */
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
        $this->getElasticsearchClient()->indices()->delete(['index' => '*']);

        parent::tearDown();
    }
}
