<?php

namespace App\SearchRepository;

use App\Entity\Packages\Package;
use App\Repository\PackageRepository;
use Elasticsearch\Client;

class PackageSearchRepository
{
    /** @var Client */
    private $client;

    /** @var PackageRepository */
    private $packageRepository;

    /**
     * @param PackageRepository $packageRepository
     * @param Client $client
     */
    public function __construct(PackageRepository $packageRepository, Client $client)
    {
        $this->packageRepository = $packageRepository;
        $this->client = $client;
    }

    /**
     * @param int $offset
     * @param int $limit
     * @param string $query
     * @param string $architecture
     * @param string|null $repository
     * @return array<mixed>
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

            //@TODO: Re-Add boosting
            $bool['should'][] = ['query_string' => ['query' => $query]];

            $bool['minimum_should_match'] = 2;
        }
        $bool['must'][] = ['term' => ['repository.architecture' => $architecture]];
        if ($repository) {
            $bool['must'][] = ['term' => ['repository.name' => $repository]];
        }
        $bool['should'][] = ['term' => ['repository.testing' => ['value' => false]]];

        $results = $this->client->search(
            [
                'index' => 'package',
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
            'repositories' => array_map(
                fn(array $repository): string => $repository['key'],
                $results['aggregations']['repository']['buckets']
            ),
            'architectures' => array_map(
                fn(array $repository): string => $repository['key'],
                $results['aggregations']['architecture']['buckets']
            )
        ];
    }

    /**
     * @param array<mixed> $results
     * @return Package[]
     */
    private function findBySearchResults(array $results): array
    {
        $ids = array_map(fn(array $result): string => $result['_id'], $results['hits']['hits']);
        $packages = $this->packageRepository->findBy(['id' => $ids]);

        $positions = array_flip($ids);
        usort($packages, fn(Package $a, Package $b): int => $positions[$a->getId()] <=> $positions[$b->getId()]);

        return $packages;
    }

    /**
     * @param string $term
     * @param int $limit
     * @return Package[]
     */
    public function findByTerm(string $term, int $limit): array
    {
        $results = $this->client->search(
            [
                'index' => 'package',
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
