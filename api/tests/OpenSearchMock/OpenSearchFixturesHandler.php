<?php

namespace App\Tests\OpenSearchMock;

use OpenSearch\Client;
use OpenSearch\ClientFactoryInterface;
use OpenSearch\EndpointFactory;
use OpenSearch\HttpClient\SymfonyHttpClientFactory;
use OpenSearch\RequestFactory;
use OpenSearch\Serializers\SmartSerializer;
use OpenSearch\TransportFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class OpenSearchFixturesHandler implements ClientFactoryInterface
{
    public function __construct(
        private readonly string $mode,
        private readonly SerializerInterface $serializer,
        protected int $maxRetries = 0,
        protected ?LoggerInterface $logger = null,
    ) {
    }

    public function create(array $options): Client
    {
        $psr18Client = new SymfonyHttpClientFactory($this->maxRetries, $this->logger)->create($options);

        if ($this->mode == 'off') {
            $httpClient = $psr18Client;
        } else {
            $httpClient = new MockPSR18Client($psr18Client, $this->mode, $this->serializer);
        }

        $serializer = new SmartSerializer();

        $requestFactory = new RequestFactory(
            $httpClient,
            $httpClient,
            $httpClient,
            $serializer,
        );

        $transport = new TransportFactory()
            ->setHttpClient($httpClient)
            ->setRequestFactory($requestFactory)
            ->create();

        $endpointFactory = new EndpointFactory();
        return new Client($transport, $endpointFactory, []);
    }
}
