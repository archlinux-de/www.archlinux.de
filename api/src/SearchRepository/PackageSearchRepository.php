<?php

namespace App\SearchRepository;

use App\Entity\Packages\Package;
use App\Repository\PackageRepository;
use App\SearchIndex\PackageSearchIndexer;
use OpenSearch\Client;

class PackageSearchRepository
{
    public function __construct(
        private readonly PackageRepository $packageRepository,
        private readonly Client $client,
        private readonly PackageSearchIndexer $packageSearchIndexer
    ) {
    }

    /**
     * @return array{
     *     'offset': int,
     *     'limit': int,
     *     'total': int,
     *     'count': int,
     *     'items': Package[],
     *     'repositories': string[],
     *     'architectures': string[]
     * }
     */
    public function findLatestByQueryAndArchitecture(
        int $offset,
        int $limit,
        string $query,
        string $architecture,
        ?string $repository
    ): array {
        $sort = [];
        if ($query) {
            $sort[] = ['_score' => ['order' => 'desc']];
            $sort[] = ['popularity' => ['order' => 'desc']];
        }
        $sort[] = ['buildDate' => ['order' => 'desc']];

        $bool = [];
        if ($query) {
            $bool['should'][] = ['term' => ['name' => ['value' => $query, 'boost' => 7]]];
            $bool['should'][] = ['term' => ['base' => ['value' => $query, 'boost' => 6]]];

            $bool['should'][] = ['wildcard' => ['name' => '*' . $query . '*']];
            $bool['should'][] = ['wildcard' => ['description' => '*' . $query . '*']];

            $bool['should'][] = [
                'multi_match' => [
                    'query' => $query,
                    'fields' => [
                        'name^5',
                        'base^4',
                        'description^3',
                        'url',
                        'groups^2',
                        'replacements',
                        'provisions',
                        'files'
                    ]
                ]
            ];

            $bool['minimum_should_match'] = 1;
        }
        $bool['must'][] = ['term' => ['repository.architecture' => $architecture]];
        if ($repository) {
            $bool['must'][] = ['term' => ['repository.name' => $repository]];
        }

        /** @var array{'hits': array{'hits': array<array{'_id': string}>, 'total': array{'value': int}}} $results */
        $results = $this->client->search(
            [
                'index' => $this->packageSearchIndexer->getIndexName(),
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
                    'aggs' => [
                        'repository' => [
                            'terms' => ['field' => 'repository.name']
                        ],
                        'architecture' => [
                            'terms' => ['field' => 'repository.architecture']
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

        $packages = $this->findBySearchResults($results);

        return [
            'offset' => $offset,
            'limit' => $limit,
            'total' => $results['hits']['total']['value'],
            'count' => count($packages),
            'items' => $packages,
            'repositories' => isset($results['aggregations']['repository']['buckets']) ? array_map(
                fn(array $repository): string => $repository['key'], // @phpstan-ignore return.type, argument.type
                $results['aggregations']['repository']['buckets'] // @phpstan-ignore argument.type
            ) : [],
            'architectures' => isset($results['aggregations']['architecture']['buckets']) ? array_map(
                fn(array $repository): string => $repository['key'], // @phpstan-ignore return.type, argument.type
                $results['aggregations']['architecture']['buckets'] // @phpstan-ignore argument.type
            ) : []
        ];
    }

    /**
     * @param array{'hits': array{'hits': array<array{'_id': string}>}} $results
     * @return Package[]
     */
    private function findBySearchResults(array $results): array
    {
        if (!$results['hits']['hits']) {
            return [];
        }

        $ids = array_map(fn(array $result): string => $result['_id'], $results['hits']['hits']);
        /** @var Package[] $packages */
        $packages = $this->packageRepository->findBy(['id' => $ids]);

        /** @var array<int, string> $positions */
        $positions = array_flip($ids);
        usort($packages, fn(Package $a, Package $b): int
        => $a->getId() && $b->getId() ? $positions[$a->getId()] <=> $positions[$b->getId()] : 0);

        return $packages;
    }

    /**
     * @return Package[]
     */
    public function findByTerm(string $term, int $limit): array
    {
        if (!$term) {
            return [];
        }

        /** @var array{'hits': array{'hits': array<array{'_id': string}>, 'total': array{'value': int}}} $results */
        $results = $this->client->search(
            [
                'index' => $this->packageSearchIndexer->getIndexName(),
                'body' => [
                    'query' => [
                        'prefix' => ['name' => $term]
                    ],
                    'sort' => [
                        'popularity' => ['order' => 'desc'],
                        '_score' => ['order' => 'desc']
                    ]
                ],
                'size' => $limit,
                '_source' => false
            ]
        );

        return $this->findBySearchResults($results);
    }
}
