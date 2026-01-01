<?php

namespace App\Service;

use App\Dto\AppStreamDataComponentDto;
use App\Repository\RepositoryRepository;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

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
        private XmlExtractor $xmlExtractor,
    ) {
    }

    /**
     * @throws \RuntimeException
     * @throws ClientExceptionInterface
     * @throws ExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
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
                $fetchedXml = $this->xmlExtractor->downloadAndExtract($upstreamUrl);
                $deserializedComponents =
                    $this
                        ->serializer
                        ->deserialize($fetchedXml, AppStreamDataComponentDto::class . '[]', 'xml');
                foreach ($deserializedComponents as $component) {
                    yield $component;
                }
            } catch (\Exception $e) {
                error_log("Failed to fetch or process data from $upstreamUrl: " . $e->getMessage());
                continue;
            }
        }
    }
}
