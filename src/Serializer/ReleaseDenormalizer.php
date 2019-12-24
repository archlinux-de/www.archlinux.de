<?php

namespace App\Serializer;

use App\Entity\Release;
use App\Entity\Torrent;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class ReleaseDenormalizer implements DenormalizerInterface
{
    /**
     * @param array<mixed> $data
     * @param string $type
     * @param string|null $format
     * @param array<mixed> $context
     * @return Release[]
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        return [
            ...(function () use ($data) {
                foreach ($data['releases'] as $releaseData) {
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
            })()
        ];
    }

    /**
     * @inheritDoc
     */
    public function supportsDenormalization($data, string $type, string $format = null)
    {
        return $type == Release::class . '[]';
    }
}
