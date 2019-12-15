<?php

namespace App\Service;

use App\Entity\Release;
use App\Entity\Torrent;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @phpstan-implements \IteratorAggregate<Release>
 */
class ReleaseFetcher implements \IteratorAggregate
{
    /** @var HttpClientInterface */
    private $httpClient;

    /** @var string */
    private $releaseUrl;

    /**
     * @param HttpClientInterface $httpClient
     * @param string $releaseUrl
     */
    public function __construct(HttpClientInterface $httpClient, string $releaseUrl)
    {
        $this->httpClient = $httpClient;
        $this->releaseUrl = $releaseUrl;
    }

    /**
     * @return \Traversable<Release>
     */
    public function getIterator(): \Traversable
    {
        foreach ($this->fetchRelengReleases() as $releaseData) {
            $release = new Release($releaseData['version']);
            $release
                ->setAvailable($releaseData['available'])
                ->setInfo($releaseData['info'])
                ->setIsoUrl($releaseData['iso_url'])
                ->setMd5Sum($releaseData['md5_sum'])
                ->setCreated(new \DateTime($releaseData['created']))
                ->setKernelVersion($releaseData['kernel_version'])
                ->setReleaseDate(new \DateTime($releaseData['release_date']))
                ->setSha1Sum($releaseData['sha1_sum']);
            if ($releaseData['torrent']) {
                $release->setTorrent(
                    (new Torrent())
                        ->setUrl($releaseData['torrent_url'])
                        ->setComment($releaseData['torrent']['comment'])
                        ->setInfoHash($releaseData['torrent']['info_hash'])
                        ->setPieceLength($releaseData['torrent']['piece_length'])
                        ->setFileName($releaseData['torrent']['file_name'])
                        ->setAnnounce($releaseData['torrent']['announce'])
                        ->setFileLength($releaseData['torrent']['file_length'])
                        ->setPieceCount($releaseData['torrent']['piece_count'])
                        ->setCreatedBy($releaseData['torrent']['created_by'])
                        ->setCreationDate(new \DateTime($releaseData['torrent']['creation_date']))
                        ->setMagnetUri($releaseData['magnet_uri'])
                );
            }

            yield $release;
        }
    }

    /**
     * @return array<mixed>
     */
    private function fetchRelengReleases(): array
    {
        $response = $this->httpClient->request('GET', $this->releaseUrl);
        $content = $response->getContent();
        if (empty($content)) {
            throw new \RuntimeException('empty releng releases');
        }
        $releng = json_decode($content, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            throw new \RuntimeException('could not decode releng releases');
        }
        if ($releng['version'] != 1) {
            throw new \RuntimeException('incompatible releng/releases version');
        }
        if (empty($releng['releases'])) {
            throw new \RuntimeException('there are no releases');
        }

        return $releng['releases'];
    }
}
