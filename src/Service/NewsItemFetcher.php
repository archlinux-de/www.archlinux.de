<?php

namespace App\Service;

use App\Entity\NewsItem;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @phpstan-implements \IteratorAggregate<NewsItem>
 */
class NewsItemFetcher implements \IteratorAggregate
{
    /** @var string */
    private $newsFeedUrl;

    /** @var HttpClientInterface */
    private $httpClient;

    /** @var SerializerInterface */
    private $serializer;

    /**
     * @param string $newsFeedUrl
     * @param HttpClientInterface $httpClient
     * @param SerializerInterface $serializer
     */
    public function __construct(string $newsFeedUrl, HttpClientInterface $httpClient, SerializerInterface $serializer)
    {
        $this->newsFeedUrl = $newsFeedUrl;
        $this->httpClient = $httpClient;
        $this->serializer = $serializer;
    }

    /**
     * @return \Traversable<NewsItem>
     */
    public function getIterator(): \Traversable
    {
        $response = $this->httpClient->request('GET', $this->newsFeedUrl);
        $content = $response->getContent();

        $newsItems = $this->serializer->deserialize($content, NewsItem::class . '[]', 'xml');

        if (empty($newsItems)) {
            throw new \RuntimeException('empty news feed');
        }

        return new \ArrayIterator($newsItems);
    }
}
