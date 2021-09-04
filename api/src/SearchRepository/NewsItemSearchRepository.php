<?php

namespace App\SearchRepository;

use App\Entity\NewsItem;
use App\Repository\NewsItemRepository;
use App\SearchIndex\NewsSearchIndexer;
use Elasticsearch\Client;

class NewsItemSearchRepository
{
    public function __construct(
        private Client $client,
        private NewsItemRepository $newsItemRepository,
        private NewsSearchIndexer $newsSearchIndexer
    ) {
    }

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
     * @return NewsItem[]
     */
    private function findBySearchResults(array $results): array
    {
        $ids = array_map(fn(array $result): string => $result['_id'], $results['hits']['hits']);
        /** @var NewsItem[] $newsItems */
        $newsItems = $this->newsItemRepository->findBy(['id' => $ids]);

        /** @var array<int,string> $positions */
        $positions = array_flip($ids);
        usort($newsItems, fn(NewsItem $a, NewsItem $b): int => $positions[$a->getId()] <=> $positions[$b->getId()]);

        return $newsItems;
    }
}
