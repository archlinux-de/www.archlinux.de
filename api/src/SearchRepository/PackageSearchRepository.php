<?php

namespace App\SearchRepository;

use App\Entity\Packages\Package;
use App\Repository\PackageRepository;
use App\SearchIndex\PackageSearchIndexer;
use Elasticsearch\Client;

class PackageSearchRepository
{
    public function __construct(
        private PackageRepository $packageRepository,
        private Client $client,
        private PackageSearchIndexer $packageSearchIndexer
    ) {
    }

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
                fn(array $repository): string => $repository['key'],
                $results['aggregations']['repository']['buckets']
            ) : [],
            'architectures' => isset($results['aggregations']['architecture']['buckets']) ? array_map(
                fn(array $repository): string => $repository['key'],
                $results['aggregations']['architecture']['buckets']
            ) : []
        ];
    }

    /**
     * @return Package[]
     */
    private function findBySearchResults(array $results): array
    {
        $ids = array_map(fn(array $result): string => $result['_id'], $results['hits']['hits']);
        /** @var Package[] $packages */
        $packages = $this->packageRepository->findBy(['id' => $ids]);

        /** @var array<int, string> $positions */
        $positions = array_flip($ids);
        usort($packages, fn(Package $a, Package $b): int => $positions[$a->getId()] <=> $positions[$b->getId()]);

        return $packages;
    }

    /**
     * @return Package[]
     */
    public function findByTerm(string $term, int $limit): array
    {
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
