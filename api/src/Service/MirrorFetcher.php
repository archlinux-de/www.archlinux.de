<?php

namespace App\Service;

use App\Entity\Mirror;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MirrorFetcher implements \IteratorAggregate
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $mirrorStatusUrl,
        private SerializerInterface $serializer
    ) {
    }

    public function getIterator(): \Traversable
    {
        $response = $this->httpClient->request('GET', $this->mirrorStatusUrl);
        $content = $response->getContent();
        if (empty($content)) {
            throw new \RuntimeException('empty mirrorstatus');
        }
        $mirrors = $this->serializer->deserialize($content, Mirror::class . '[]', 'json');

        if (empty($mirrors)) {
            throw new \RuntimeException('mirrorlist is empty');
        }

        return new \ArrayIterator($mirrors);
    }
}
