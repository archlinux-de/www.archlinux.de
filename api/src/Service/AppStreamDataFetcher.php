<?php

namespace App\Service;

use App\Entity\Packages\Metadata;
use App\Repository\PackageRepository;
use App\Serializer\AppStreamDataDenormalizer;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * @implements \IteratorAggregate<string, Metadata>
 */
readonly class AppStreamDataFetcher implements \IteratorAggregate
{
    public function __construct(
        private string $appStreamDataBaseUrl,
        private string $appStreamDataFile,
        private string $repoToFetchFor,
        private PackageRepository $packageRepository
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
        $appStreamData = $this->packageRepository->getByName(
            repository: 'extra',
            architecture: 'any',
            name:'app-stream-data'
        );
        $asdYmd =  strtok($appStreamData->getVersion(), '-');

        $upstreamUrl = $this->appStreamDataBaseUrl . $asdYmd . '/' . $this->repoToFetchFor . $this->appStreamDataFile;

        $fetchedXml = $this->downloadAndExtract($upstreamUrl);

        $denormalizer = new AppStreamDataDenormalizer();

        foreach ($denormalizer->denormalize($fetchedXml) as $pkgname => $metaData) {
            yield $pkgname => $metaData;
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
