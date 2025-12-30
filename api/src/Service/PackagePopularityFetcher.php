<?php

namespace App\Service;

use App\Entity\Packages\Popularity;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @implements \IteratorAggregate<string, Popularity>
 */
readonly class PackagePopularityFetcher implements \IteratorAggregate
{
    public function __construct(private string $packageStatisticsApiUrl, private HttpClientInterface $httpClient)
    {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getIterator(): \Traversable
    {
        $count = 1;
        $offset = 0;
        $limit = 10000;

        while ($count !== 0) { // @phpstan-ignore notIdentical.alwaysTrue
            $response = $this->httpClient->request(
                'GET',
                $this->packageStatisticsApiUrl,
                [
                    'query' => ['offset' => $offset, 'limit' => $limit],
                    'headers' => ['Accept' => 'application/json'],
                ]
            );
            $content = $response->getContent();
            /** @var array{'packagePopularities': list<array{'name': string, 'popularity': float, 'samples': int, 'count': int}>} $packagePopularityList */
            $packagePopularityList = json_decode($content, true);
            if (!is_array($packagePopularityList)) {
                throw new \RuntimeException('Invalid packagePopularityList');
            }

            $count = 0;
            foreach ($packagePopularityList['packagePopularities'] as $packagePopularity) {
                yield $packagePopularity['name'] => new Popularity(
                    $packagePopularity['popularity'],
                    $packagePopularity['samples'],
                    $packagePopularity['count']
                );
                $count++;
            }
            $offset += $count;
            if ($count < $limit) {
                break;
            }
        }
    }
}
