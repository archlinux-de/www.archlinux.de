<?php

namespace App\Service;

use App\Entity\Release;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ReleaseFetcher implements \IteratorAggregate
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $releaseUrl,
        private SerializerInterface $serializer
    ) {
    }

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
