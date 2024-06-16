<?php

namespace App\Service;

use App\Entity\NewsItem;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @implements \IteratorAggregate<NewsItem>
 */
class NewsItemFetcher implements \IteratorAggregate
{
    public function __construct(
        private readonly string $flarumUrl,
        private readonly string $flarumTag,
        private readonly HttpClientInterface $httpClient,
        private readonly SerializerInterface $serializer
    ) {
    }

    public function getIterator(): \Traversable
    {
        $offset = 0;
        $limit = 50;

        do {
            $response = $this->httpClient->request(
                'GET',
                '/api/discussions',
                [
                    'base_uri' => $this->flarumUrl,
                    'query' => [
                        'include' => 'user,firstPost',
                        'filter' => ['tag' => $this->flarumTag],
                        'page' => ['offset' => $offset, 'limit' => $limit]
                    ]
                ]
            );
            $content = $response->getContent();
            $newsItems = $this->serializer->deserialize($content, NewsItem::class . '[]', 'json');

            yield from $newsItems;

            $count = count($newsItems);
            $offset += $count;

            if ($count < $limit) {
                break;
            }
        } while ($count != 0);
    }
}
