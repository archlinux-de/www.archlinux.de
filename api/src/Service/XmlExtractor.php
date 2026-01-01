<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class XmlExtractor
{
    public function __construct(private HttpClientInterface $httpClient)
    {
    }
    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws \RuntimeException
     */
    public function downloadAndExtract(string $url): string
    {
        try {
            $response = $this->httpClient->request('GET', $url);
            $compressedContent = $response->getContent();
        } catch (
            TransportExceptionInterface |
            ClientExceptionInterface |
            RedirectionExceptionInterface |
            ServerExceptionInterface $e
        ) {
            throw new \RuntimeException(sprintf(
                'Failed to download appstream data from %s: %s',
                $url,
                $e->getMessage()
            ), 0, $e);
        }

        $xmlContent = gzdecode($compressedContent);

        if ($xmlContent === false) {
            throw new \RuntimeException("Failed to decompress: $url");
        }

        return $xmlContent;
    }
}
