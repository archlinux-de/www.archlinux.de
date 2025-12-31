<?php

namespace App\Service;

use App\Dto\AppStreamDataComponentDto;
use App\Exception\AppStreamDataPackageNotFoundException;
use App\Exception\AppStreamDataUnavailableException;
use App\Repository\RepositoryRepository;
use Psr\Log\LoggerInterface;
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
        private LoggerInterface $logger,
    ) {
    }

    public function getIterator(): \Traversable
    {
        try {
            $version = $this->appStreamDataVersionObtainer->obtainAppStreamDataVersion();
        } catch (AppStreamDataPackageNotFoundException $e) {
            $this->logger->critical($e->getMessage());
            return;
        }

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
            } catch (AppStreamDataUnavailableException $e) {
                $this->logger->error($e->getMessage());
                continue;
            } catch (ExceptionInterface $e) {
                $this->logger->error(sprintf('Failed to deserialize appstream data from %s: %s', $upstreamUrl, $e->getMessage()));
            }
        }
    }

    /**
     * @throws AppStreamDataUnavailableException
     */
    private function downloadAndExtract(string $url): string
    {
        $httpClient = HttpClient::create();
        try {
            $response = $httpClient->request('GET', $url);
            $compressedContent = $response->getContent();
        } catch (TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $e) {
            throw new AppStreamDataUnavailableException(sprintf('Failed to download appstream data from %s: %s', $url, $e->getMessage()), 0, $e);
        }

        $xmlContent = gzdecode($compressedContent);

        if ($xmlContent === false) {
            throw new AppStreamDataUnavailableException("Failed to decompress: $url");
        }

        return $xmlContent;
    }
}
