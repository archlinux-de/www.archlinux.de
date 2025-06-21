<?php

namespace App\Tests;

use App\SearchIndex\MirrorSearchIndexer;
use App\SearchIndex\NewsSearchIndexer;
use App\SearchIndex\PackageSearchIndexer;
use App\SearchIndex\ReleaseSearchIndexer;
use App\SearchIndex\SearchIndexConfigurationInterface;
use OpenSearch\Client;
use OpenSearch\Exception\NotFoundHttpException;
use SymfonyDatabaseTest\DatabaseTestCase;

abstract class DatabaseSearchTestCase extends DatabaseTestCase
{
    /**
     * @var SearchIndexConfigurationInterface[]
     */
    private array $searchIndexers = [];

    #[\Override]
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
        $openSearchClient = $this->getOpenSearchClient();

        try {
            $openSearchClient->indices()->delete(['index' => $searchIndexer->getIndexName()]);
        } catch (NotFoundHttpException $_) {
        }
        $openSearchClient->indices()->create($searchIndexer->createIndexConfiguration());
    }

    private function getOpenSearchClient(): Client
    {
        $container = static::getClient()->getContainer();
        $client = $container->get(Client::class);
        $this->assertInstanceOf(Client::class, $client);
        return $client;
    }

    #[\Override]
    public function tearDown(): void
    {
        foreach ($this->searchIndexers as $searchIndexer) {
            $this->getOpenSearchClient()->indices()->delete(['index' => $searchIndexer->getIndexName()]);
        }

        parent::tearDown();
    }
}
