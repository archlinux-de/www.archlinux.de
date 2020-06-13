<?php

namespace App\SearchRepository;

use App\Entity\Release;
use App\Repository\ReleaseRepository;
use Elasticsearch\Client;

class ReleaseSearchRepository
{
    /** @var Client */
    private $client;

    /** @var ReleaseRepository */
    private $releaseRepository;

    /**
     * @param Client $client
     * @param ReleaseRepository $releaseRepository
     */
    public function __construct(Client $client, ReleaseRepository $releaseRepository)
    {
        $this->client = $client;
        $this->releaseRepository = $releaseRepository;
    }

    /**
     * @param int $offset
     * @param int $limit
     * @param string $query
     * @return array
     */
    public function findAllByQuery(int $offset, int $limit, string $query): array
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

            $bool['should'][] = ['multi_match' => ['query' => $query]];

            $bool['minimum_should_match'] = 1;
        }

        $body = ['sort' => $sort];
        if ($bool) {
            $body['query'] = ['bool' => $bool];
        }

        $results = $this->client->search(
            [
                'index' => 'release',
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
     * @param array $results
     * @return Release[]
     */
    private function findBySearchResults(array $results): array
    {
        $ids = array_map(fn(array $result): string => $result['_id'], $results['hits']['hits']);
        $releases = $this->releaseRepository->findBy(['version' => $ids]);

        $positions = array_flip($ids);
        usort(
            $releases,
            fn(Release $a, Release $b): int => $positions[$a->getVersion()] <=> $positions[$b->getVersion()]
        );

        return $releases;
    }
}
