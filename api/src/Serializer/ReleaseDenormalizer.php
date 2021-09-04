<?php

namespace App\Serializer;

use App\Entity\Release;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class ReleaseDenormalizer implements DenormalizerInterface, CacheableSupportsMethodInterface
{
    /**
     * @param array $data
     * @return Release[]
     */
    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): array
    {
        return [
            ...(function () use ($data) {
                foreach ($data['releases'] as $releaseData) {
                    $release = (new Release($releaseData['version']))
                        ->setAvailable($releaseData['available'])
                        ->setInfo($releaseData['info'])
                        ->setCreated(new \DateTime($releaseData['created']))
                        ->setKernelVersion($releaseData['kernel_version'])
                        ->setReleaseDate(new \DateTime($releaseData['release_date']))
                        ->setSha1Sum($releaseData['sha1_sum']);
                    if ($releaseData['torrent']) {
                        $release
                            ->setTorrentUrl($releaseData['torrent_url'])
                            ->setFileName($releaseData['torrent']['file_name'])
                            ->setFileLength($releaseData['torrent']['file_length'])
                            ->setMagnetUri($releaseData['magnet_uri']);
                    }

                    yield $release;
                }
            })()
        ];
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null): bool
    {
        return $type == Release::class . '[]';
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
