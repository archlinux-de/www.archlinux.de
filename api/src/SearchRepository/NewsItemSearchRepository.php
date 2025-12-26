<?php

namespace App\SearchRepository;

use App\Entity\NewsItem;
use App\Repository\NewsItemRepository;
use App\SearchIndex\NewsSearchIndexer;
use OpenSearch\Client;

class NewsItemSearchRepository
{
    public function __construct(
        private readonly Client $client,
        private readonly NewsItemRepository $newsItemRepository,
        private readonly NewsSearchIndexer $newsSearchIndexer
    ) {
    }

    /**
     * @return array{'offset': int, 'limit': int, 'total': int, 'count': int, 'items': NewsItem[]}
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
            $isQuoted = str_starts_with($query, '"') && str_ends_with($query, '"');
            if ($isQuoted) {
                $query = trim($query, '"');
            }

            $multiMatch = [
                'query' => $query,
                'fields' => [
                    'title^2',
                    'description',
                    'author',
                ],
            ];

            if ($isQuoted) {
                $bool['should'][] = ['match_phrase' => ['title' => ['query' => $query, 'boost' => 2]]];
                $bool['should'][] = ['match_phrase' => ['description' => $query]];
                $bool['should'][] = ['match_phrase' => ['author' => $query]];
                $multiMatch['type'] = 'phrase';
            } else {
                $bool['should'][] = ['wildcard' => ['title' => ['value' => '*' . $query . '*', 'boost' => 2]]];
                $bool['should'][] = ['wildcard' => ['description' => '*' . $query . '*']];
                $bool['should'][] = ['wildcard' => ['author' => '*' . $query . '*']];
            }

            $bool['should'][] = ['multi_match' => $multiMatch];
            $bool['minimum_should_match'] = 1;
        }

        $body = ['sort' => $sort];
        if ($bool) {
            $body['query'] = ['bool' => $bool];
        }

        /** @var array{'hits': array{'hits': array<array{'_id': string}>, 'total': array{'value': int}}} $results */
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
     * @param array{'hits': array{'hits': array<array{'_id': string}>}} $results
     * @return NewsItem[]
     */
    private function findBySearchResults(array $results): array
    {
        if (!$results['hits']['hits']) {
            return [];
        }

        $ids = array_map(fn(array $result): string => $result['_id'], $results['hits']['hits']);
        /** @var NewsItem[] $newsItems */
        $newsItems = $this->newsItemRepository->findBy(['id' => $ids]);

        /** @var array<int,string> $positions */
        $positions = array_flip($ids);
        usort($newsItems, fn(NewsItem $a, NewsItem $b): int => $positions[$a->getId()] <=> $positions[$b->getId()]);

        return $newsItems;
    }
}
