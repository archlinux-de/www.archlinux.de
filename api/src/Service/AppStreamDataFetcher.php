<?php

namespace App\Service;

use App\Dto\AppStreamDataComponentDto;
use App\Repository\RepositoryRepository;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * @implements \IteratorAggregate<int, AppStreamDataComponentDto>
 */
readonly class AppStreamDataFetcher implements \IteratorAggregate
{
    public function __construct(
        private string $appStreamDataBaseUrl,
        private string $appStreamDataFile,
        private AppStreamDataVersionObtainer $appStreamDataVersionObtainer,
        private SerializerInterface $serializer,
        private RepositoryRepository $repositoryRepository,
    ) {
    }

    /**
     * @throws \RuntimeException
     * @throws ClientExceptionInterface
     * @throws ExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getIterator(): \Traversable
    {
        $version = $this->appStreamDataVersionObtainer->obtainAppStreamDataVersion();

        $reposToFetchFor = $this->repositoryRepository->findBy(['testing' => false]);

        foreach ($reposToFetchFor as $repo) {
            $upstreamUrl =
                $this->appStreamDataBaseUrl .
                '/' .
                $version .
                '/' .
                $repo->getName() .
                '/' .
                $this->appStreamDataFile;

            try {
                $fetchedXml = $this->downloadAndExtract($upstreamUrl);
                $deserializedComponents = $this->serializer->deserialize($fetchedXml, AppStreamDataComponentDto::class . '[]', 'xml');
                foreach ($deserializedComponents as $component) {
                    yield $component;
                }
            } catch (\Exception $e) {
                // For now, we can log them and continue.
                error_log("Failed to fetch or process data from $upstreamUrl: " . $e->getMessage());
                continue;
            }
        }
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws \RuntimeException
     */
    private function downloadAndExtract(string $url): string
    {
        $httpClient = HttpClient::create();
        try {
            $response = $httpClient->request('GET', $url);
            $compressedContent = $response->getContent();
        } catch (TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $e) {
            throw new \RuntimeException(sprintf('Failed to download appstream data from %s: %s', $url, $e->getMessage()), 0, $e);
        }


        $xmlContent = gzdecode($compressedContent);

        if ($xmlContent === false) {
            throw new \RuntimeException("Failed to decompress: $url");
        }

        return $xmlContent;
    }
}
