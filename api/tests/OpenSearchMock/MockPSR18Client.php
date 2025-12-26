<?php

namespace App\Tests\OpenSearchMock;

use Nyholm\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Component\Serializer\SerializerInterface;

class MockPSR18Client implements
    ClientInterface,
    RequestFactoryInterface,
    StreamFactoryInterface,
    UriFactoryInterface
{
    private string $fixturesDirectory = __DIR__ . '/Fixtures';

    public function __construct(
        private readonly Psr18Client $psr18Client,
        private readonly string $mode,
        private readonly SerializerInterface $serializer
    ) {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $requestIdentifier = [
            $request->getMethod(),
            $request->getUri(),
            $request->getBody()
        ];
        $filename = $this->fixturesDirectory . '/' . hash('sha256', implode(':', $requestIdentifier)) . '.json';

        if ($this->mode === 'read') {
            if (!file_exists($filename)) {
                throw new RuntimeException(
                    sprintf('Fixture not found for request "%s"', implode('; ', $requestIdentifier))
                );
            }
            $response = $this->serializer->deserialize(
                (string)file_get_contents($filename),
                Response::class,
                'json'
            );
        } elseif ($this->mode === 'write') {
            $response = $this->psr18Client->sendRequest($request);
            file_put_contents($filename, $this->serializer->serialize($response, 'json'));
            $response->getBody()->rewind();
        } else {
            throw new RuntimeException(sprintf('Unsupported mode %s', $this->mode));
        }

        return $response;
    }

    public function createRequest(string $method, $uri): RequestInterface
    {
        return $this->psr18Client->createRequest($method, $uri);
    }

    public function createStream(string $content = ''): StreamInterface
    {
        return $this->psr18Client->createStream($content);
    }

    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        return $this->psr18Client->createStreamFromFile($filename, $mode);
    }

    public function createStreamFromResource($resource): StreamInterface
    {
        return $this->psr18Client->createStreamFromResource($resource);
    }

    public function createUri(string $uri = ''): UriInterface
    {
        return $this->psr18Client->createUri($uri);
    }
}
