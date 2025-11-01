<?php

namespace App\SearchRepository;

use App\Entity\Release;
use App\Repository\ReleaseRepository;
use App\SearchIndex\ReleaseSearchIndexer;
use OpenSearch\Client;

class ReleaseSearchRepository
{
    public function __construct(
        private readonly Client $client,
        private readonly ReleaseRepository $releaseRepository,
        private readonly ReleaseSearchIndexer $releaseSearchIndexer
    ) {
    }

    /**
     * @return array{'offset': int, 'limit': int, 'total': int, 'count': int, 'items': Release[]}
     */
    public function findAllByQuery(int $offset, int $limit, string $query, bool $onlyAvailable = false): array
    {
        $sort = [];
        if ($query) {
            $sort[] = ['_score' => ['order' => 'desc']];
        }
        $sort[] = ['releaseDate' => ['order' => 'desc']];

        $bool = [];
        if ($query) {
            $bool['should'][] = ['wildcard' => ['version' => ['value' => '*' . $query . '*', 'boost' => 2]]];
            $bool['should'][] = ['wildcard' => ['kernelVersion' => '*' . $query . '*']];
            $bool['should'][] = ['wildcard' => ['info' => '*' . $query . '*']];

            $bool['should'][] = [
                'multi_match' => [
                    'query' => $query,
                    'fields' => [
                        'version^2',
                        'kernelVersion',
                        'info',
                    ]
                ]
            ];

            $bool['minimum_should_match'] = 1;
        }

        if ($onlyAvailable) {
            $bool['must'][] = ['term' => ['available' => true]];
        }

        $body = ['sort' => $sort];
        if ($bool) {
            $body['query'] = ['bool' => $bool];
        }

        /** @var array{'hits': array{'hits': array<array{'_id': string}>, 'total': array{'value': int}}} $results */
        $results = $this->client->search(
            [
                'index' => $this->releaseSearchIndexer->getIndexName(),
                'body' => $body,
                'from' => $offset,
                'size' => $limit,
                '_source' => false,
                'track_total_hits' => true
            ]
        );

        $releases = $this->findBySearchResults($results);

        return [
            'offset' => $offset,
            'limit' => $limit,
            'total' => $results['hits']['total']['value'],
            'count' => count($releases),
            'items' => $releases
        ];
    }

    /**
     * @param array{'hits': array{'hits': array<array{'_id': string}>}} $results
     * @return Release[]
     */
    private function findBySearchResults(array $results): array
    {
        if (!$results['hits']['hits']) {
            return [];
        }

        $ids = array_map(fn(array $result): string => $result['_id'], $results['hits']['hits']);
        /** @var Release[] $releases */
        $releases = $this->releaseRepository->findBy(['version' => $ids]);

        $positions = array_flip($ids);
        usort(
            $releases,
            fn(Release $a, Release $b): int => $positions[$a->getVersion()] <=> $positions[$b->getVersion()]
        );

        return $releases;
    }
}
