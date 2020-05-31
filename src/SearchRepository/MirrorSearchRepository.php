<?php

namespace App\SearchRepository;

use App\Entity\Mirror;
use App\Repository\MirrorRepository;
use Elasticsearch\Client;

class MirrorSearchRepository
{
    private const PROTOCOL = 'https';

    /** @var string */
    private $mirrorCountry;

    /** @var Client */
    private $client;

    /** @var MirrorRepository */
    private $mirrorRepository;

    /**
     * @param string $mirrorCountry
     * @param Client $client
     * @param MirrorRepository $mirrorRepository
     */
    public function __construct(string $mirrorCountry, Client $client, MirrorRepository $mirrorRepository)
    {
        $this->mirrorCountry = $mirrorCountry;
        $this->client = $client;
        $this->mirrorRepository = $mirrorRepository;
    }

    /**
     * @param int $offset
     * @param int $limit
     * @param string $query
     * @return array<mixed>
     */
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
            $bool['should'][] = ['term' => ['country.code' => ['value' => $this->mirrorCountry, 'boost' => 0.1]]];
        }

        $bool['must'][] = ['term' => ['protocol' => self::PROTOCOL]];

        $results = $this->client->search(
            [
                'index' => 'mirror',
                'body' => [
                    'query' => ['bool' => $bool],
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
     * @param array<mixed> $results
     * @return Mirror[]
     */
    private function findBySearchResults(array $results): array
    {
        $ids = array_map(fn(array $result): string => $result['_id'], $results['hits']['hits']);
        $mirrors = $this->mirrorRepository->findBy(['url' => $ids]);

        $positions = array_flip($ids);
        usort(
            $mirrors,
            fn(Mirror $a, Mirror $b): int => $positions[$a->getUrl()] <=> $positions[$b->getUrl()]
        );

        return $mirrors;
    }

    /**
     * @param string $countryCode
     * @param \DateTime|null $lastSync
     * @param int $limit
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
        $bool['should'][] = ['term' => ['country.code' => $countryCode]];
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
        $bool['must'][] = ['term' => ['protocol' => self::PROTOCOL]];

        $results = $this->client->search(
            [
                'index' => 'mirror',
                'body' => [
                    'query' => ['bool' => $bool],
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
