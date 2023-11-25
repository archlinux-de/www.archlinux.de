<?php

namespace App\Service;

use App\Entity\Packages\Popularity;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PackagePopularityFetcher implements \IteratorAggregate
{
    public function __construct(private string $packageStatisticsApiUrl, private HttpClientInterface $httpClient)
    {
    }

    /**
     * @return \Traversable<string, Popularity>
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
