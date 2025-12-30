<?php

namespace App\Service;

use App\Serializer\AppStreamDataDenormalizer;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * @implements \IteratorAggregate<string, array<string>>
 */
readonly class AppStreamDataFetcher implements \IteratorAggregate
{
    public function __construct(
        private string $appStreamDataBaseUrl,
        private string $appStreamDataFile,
        private string $repoToFetchFor,
        private AppStreamDataVersionObtainer $appStreamDataVersionObtainer,
        private KeywordsCleaner $keywordCleaner
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getIterator(): \Traversable
    {
        $upstreamUrl =
            $this->appStreamDataBaseUrl .
            $this->appStreamDataVersionObtainer->obtainAppStreamDataVersion() .
            '/' .
            $this->repoToFetchFor .
            '/' .
            $this->appStreamDataFile;

        $fetchedXml = $this->downloadAndExtract($upstreamUrl);

        $denormalizer = new AppStreamDataDenormalizer($this->keywordCleaner);

        foreach ($denormalizer->denormalize($fetchedXml) as $pkgname => $keywords) {
            yield $pkgname => $keywords;
        }
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function downloadAndExtract(string $url): string
    {
        $httpClient = HttpClient::create();
        $response = $httpClient->request('GET', $url);
        $compressedContent = $response->getContent();

        // Decompress the .gz file
        $xmlContent = gzdecode($compressedContent);

        if ($xmlContent === false) {
            throw new \RuntimeException("Failed to decompress: $url");
        }

        return $xmlContent;
    }
}
