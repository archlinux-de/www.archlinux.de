<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @phpstan-implements \IteratorAggregate<string,float>
 */
class PackagePopularityFetcher implements \IteratorAggregate
{
    /** @var string */
    private $packageStatisticsApiUrl;

    /** @var HttpClientInterface */
    private $httpClient;

    /**
     * @param string $packageStatisticsApiUrl
     * @param HttpClientInterface $httpClient
     */
    public function __construct(string $packageStatisticsApiUrl, HttpClientInterface $httpClient)
    {
        $this->packageStatisticsApiUrl = $packageStatisticsApiUrl;
        $this->httpClient = $httpClient;
    }

    /**
     * @return \Traversable<string, float>
     */
    public function getIterator(): \Traversable
    {
        $count = 1;
        $offset = 0;
        $limit = 10000;

        while ($count != 0) {
            $response = $this->httpClient->request(
                'GET',
                $this->packageStatisticsApiUrl,
                [
                    'query' => ['offset' => $offset, 'limit' => $limit],
                    'json' => true
                ]
            );
            $content = $response->getContent();
            $packagePopularityList = json_decode($content, true);
            $count = 0;
            foreach ($packagePopularityList['packagePopularities'] as $packagePopularity) {
                yield $packagePopularity['name'] => $packagePopularity['popularity'];
                $count++;
            }
            $offset += $count;
            if ($count < $limit) {
                break;
            }
        }
    }
}
