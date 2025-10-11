<?php

namespace App\Service;

use App\Entity\MirrorPopularity as Popularity;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @implements \IteratorAggregate<string, Popularity>
 */
readonly class MirrorPopularityFetcher implements \IteratorAggregate
{
    public function __construct(private string $mirrorStatisticsApiUrl, private HttpClientInterface $httpClient)
    {
    }

    public function getIterator(): \Traversable
    {
        $count = 1;
        $offset = 0;
        $limit = 10000;

        while ($count !== 0) { // @phpstan-ignore notIdentical.alwaysTrue
            $response = $this->httpClient->request(
                'GET',
                $this->mirrorStatisticsApiUrl,
                [
                    'query' => ['offset' => $offset, 'limit' => $limit],
                    'headers' => ['Accept' => 'application/json'],
                ]
            );
            $content = $response->getContent();
            /** @var array{'mirrorPopularities': list<array{'url': string, 'popularity': float, 'samples': int, 'count': int}>} $mirrorPopularityList */
            $mirrorPopularityList = json_decode($content, true);
            if (!is_array($mirrorPopularityList)) {
                throw new \RuntimeException('Invalid mirrorPopularityList');
            }

            $count = 0;
            foreach ($mirrorPopularityList['mirrorPopularities'] as $mirrorPopularity) {
                yield $mirrorPopularity['url'] => new Popularity(
                    $mirrorPopularity['popularity'],
                    $mirrorPopularity['samples'],
                    $mirrorPopularity['count']
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
