<?php

namespace App\SearchRepository;

use App\Entity\Mirror;
use App\Repository\MirrorRepository;
use App\SearchIndex\MirrorSearchIndexer;
use OpenSearch\Client;

class MirrorSearchRepository
{
    public function __construct(
        private string $mirrorCountry,
        private Client $client,
        private MirrorRepository $mirrorRepository,
        private MirrorSearchIndexer $mirrorSearchIndexer
    ) {
    }

    public function findSecureByQuery(int $offset, int $limit, string $query): array
    {
        $sort = [
            '_score' => ['order' => 'desc'],
            'score' => ['order' => 'asc']
        ];

        $bool = [];
        if ($query) {
            $bool['should'][] = ['wildcard' => ['url' => '*' . $query . '*']];
            $bool['should'][] = ['wildcard' => ['country.name' => '*' . $query . '*']];

            $bool['should'][] = ['multi_match' => ['query' => $query]];

            $bool['minimum_should_match'] = 1;
        } else {
            $bool['should'][] = ['term' => ['country.code' => ['value' => $this->mirrorCountry, 'boost' => 0.5]]];
        }

        $bool['must'][] = ['wildcard' => ['url' => 'https*']];

        $results = $this->client->search(
            [
                'index' => $this->mirrorSearchIndexer->getIndexName(),
                'body' => [
                    'query' => [
                        'function_score' => [
                            'query' => ['bool' => $bool],
                            'field_value_factor' => [
                                'field' => 'popularity',
                                'factor' => 0.1,
                                'modifier' => 'sqrt',
                                'missing' => 0
                            ]
                        ]
                    ],
                    'sort' => $sort
                ],
                'from' => $offset,
                'size' => $limit,
                '_source' => false,
                'track_total_hits' => true
            ]
        );

        $mirrors = $this->findBySearchResults($results);

        return [
            'offset' => $offset,
            'limit' => $limit,
            'total' => $results['hits']['total']['value'],
            'count' => count($mirrors),
            'items' => $mirrors
        ];
    }

    /**
     * @return Mirror[]
     */
    private function findBySearchResults(array $results): array
    {
        $ids = array_map(fn(array $result): string => $result['_id'], $results['hits']['hits']);
        /** @var Mirror[] $mirrors */
        $mirrors = $this->mirrorRepository->findBy(['url' => $ids]);

        $positions = array_flip($ids);
        usort(
            $mirrors,
            fn(Mirror $a, Mirror $b): int => $positions[$a->getUrl()] <=> $positions[$b->getUrl()]
        );

        return $mirrors;
    }

    /**
     * @return Mirror[]
     */
    public function findBestByCountryAndLastSync(
        string $countryCode,
        ?\DateTime $lastSync = null,
        int $limit = 20
    ): array {
        $sort = [
            '_score' => ['order' => 'desc'],
            'score' => ['order' => 'asc']
        ];

        $bool = [];
        $bool['should'][] = ['term' => ['country.code' => ['value' => $countryCode, 'boost' => 0.5]]];
        if ($lastSync) {
            $bool['should'][] = [
                'range' => [
                    'lastSync' => [
                        'gt' => $lastSync->getTimestamp(),
                        'format' => 'epoch_second',
                        'boost' => 2
                    ]
                ]
            ];
        }
        $bool['must'][] = ['wildcard' => ['url' => 'https*']];

        $results = $this->client->search(
            [
                'index' => $this->mirrorSearchIndexer->getIndexName(),
                'body' => [
                    'query' => [
                        'function_score' => [
                            'query' => ['bool' => $bool],
                            'field_value_factor' => [
                                'field' => 'popularity',
                                'factor' => 0.1,
                                'modifier' => 'sqrt',
                                'missing' => 0
                            ]
                        ]
                    ],
                    'sort' => $sort
                ],
                'size' => $limit,
                '_source' => false,
                'track_total_hits' => true
            ]
        );

        return $this->findBySearchResults($results);
    }
}
