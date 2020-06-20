<?php

namespace App\Service;

use App\Entity\Mirror;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MirrorFetcher implements \IteratorAggregate
{
    /** @var HttpClientInterface */
    private $httpClient;

    /** @var string */
    private $mirrorStatusUrl;

    /** @var SerializerInterface */
    private $serializer;

    /**
     * @param HttpClientInterface $httpClient
     * @param string $mirrorStatusUrl
     * @param SerializerInterface $serializer
     */
    public function __construct(
        HttpClientInterface $httpClient,
        string $mirrorStatusUrl,
        SerializerInterface $serializer
    ) {
        $this->httpClient = $httpClient;
        $this->mirrorStatusUrl = $mirrorStatusUrl;
        $this->serializer = $serializer;
    }

    /**
     * @return \Traversable
     */
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
