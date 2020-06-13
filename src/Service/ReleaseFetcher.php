<?php

namespace App\Service;

use App\Entity\Release;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ReleaseFetcher implements \IteratorAggregate
{
    /** @var HttpClientInterface */
    private $httpClient;

    /** @var string */
    private $releaseUrl;

    /** @var SerializerInterface */
    private $serializer;

    /**
     * @param HttpClientInterface $httpClient
     * @param string $releaseUrl
     * @param SerializerInterface $serializer
     */
    public function __construct(HttpClientInterface $httpClient, string $releaseUrl, SerializerInterface $serializer)
    {
        $this->httpClient = $httpClient;
        $this->releaseUrl = $releaseUrl;
        $this->serializer = $serializer;
    }

    /**
     * @return \Traversable
     */
    public function getIterator(): \Traversable
    {
        $response = $this->httpClient->request('GET', $this->releaseUrl);
        $content = $response->getContent();
        if (empty($content)) {
            throw new \RuntimeException('empty releng releases');
        }
        $releases = $this->serializer->deserialize($content, Release::class . '[]', 'json');

        if (empty($releases)) {
            throw new \RuntimeException('there are no releases');
        }

        return new \ArrayIterator($releases);
    }
}
