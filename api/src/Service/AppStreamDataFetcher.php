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
 * @implements \IteratorAggregate<AppStreamDataComponentDto>
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
     * @throws ClientExceptionInterface
     * @throws ExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getIterator(): \Traversable
    {
        $reposToFetchFor = $this->repositoryRepository->findBy(['testing' => false]);

        $res = [];
        foreach ($reposToFetchFor as $repo) {
            $upstreamUrl =
                $this->appStreamDataBaseUrl .
                $this->appStreamDataVersionObtainer->obtainAppStreamDataVersion() .
                '/' .
                $repo->getName() .
                '/' .
                $this->appStreamDataFile;

            $fetchedXml = $this->downloadAndExtract($upstreamUrl);
            $des = $this->serializer->deserialize($fetchedXml, AppStreamDataComponentDto::class . '[]', 'xml');
            $res = array_merge($res, $des);
        }


        return $res;
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
