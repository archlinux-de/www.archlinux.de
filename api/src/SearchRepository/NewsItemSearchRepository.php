<?php

namespace App\SearchRepository;

use App\Entity\NewsItem;
use App\Repository\NewsItemRepository;
use App\SearchIndex\NewsSearchIndexer;
use Elasticsearch\Client;

class NewsItemSearchRepository
{
    /** @var Client */
    private $client;

    /** @var NewsItemRepository */
    private $newsItemRepository;

    /** @var NewsSearchIndexer */
    private $newsSearchIndexer;

    /**
     * @param Client $client
     * @param NewsItemRepository $newsItemRepository
     * @param NewsSearchIndexer $newsSearchIndexer
     */
    public function __construct(
        Client $client,
        NewsItemRepository $newsItemRepository,
        NewsSearchIndexer $newsSearchIndexer
    ) {
        $this->client = $client;
        $this->newsItemRepository = $newsItemRepository;
        $this->newsSearchIndexer = $newsSearchIndexer;
    }

    /**
     * @param int $offset
     * @param int $limit
     * @param string $query
     * @return array
     */
    public function findLatestByQuery(int $offset, int $limit, string $query): array
    {
        $sort = [];
        if ($query) {
            $sort[] = ['_score' => ['order' => 'desc']];
        }
        $sort[] = ['lastModified' => ['order' => 'desc']];

        $bool = [];
        if ($query) {
            $bool['should'][] = ['wildcard' => ['title' => ['value' => '*' . $query . '*', 'boost' => 2]]];
            $bool['should'][] = ['wildcard' => ['description' => '*' . $query . '*']];

            $bool['should'][] = ['multi_match' => ['query' => $query]];

            $bool['minimum_should_match'] = 1;
        }

        $body = ['sort' => $sort];
        if ($bool) {
            $body['query'] = ['bool' => $bool];
        }

        $results = $this->client->search(
            [
                'index' => $this->newsSearchIndexer->getIndexName(),
                'body' => $body,
                'from' => $offset,
                'size' => $limit,
                '_source' => false,
                'track_total_hits' => true
            ]
        );

        $newsItems = $this->findBySearchResults($results);

        return [
            'offset' => $offset,
            'limit' => $limit,
            'total' => $results['hits']['total']['value'],
            'count' => count($newsItems),
            'items' => $newsItems
        ];
    }

    /**
     * @param array $results
     * @return NewsItem[]
     */
    private function findBySearchResults(array $results): array
    {
        $ids = array_map(fn(array $result): string => $result['_id'], $results['hits']['hits']);
        $newsItems = $this->newsItemRepository->findBy(['id' => $ids]);

        $positions = array_flip($ids);
        usort($newsItems, fn(NewsItem $a, NewsItem $b): int => $positions[$a->getId()] <=> $positions[$b->getId()]);

        return $newsItems;
    }
}
