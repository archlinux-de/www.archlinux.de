<?php

namespace App\Serializer;

use App\Entity\Release;
use HTMLPurifier;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class ReleaseNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    public function __construct(
        private UrlGeneratorInterface $router,
        private ObjectNormalizer $normalizer,
        private HTMLPurifier $purifier
    ) {
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof Release && $format == 'json';
    }

    /**
     * @param Release $object
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        /** @var array $data */
        $data = $this->normalizer->normalize(
            $object,
            $format,
            array_merge(
                $context,
                [
                    AbstractNormalizer::ATTRIBUTES => [
                        'version',
                        'available',
                        'info',
                        'kernelVersion',
                        'releaseDate',
                        'sha1Sum'
                    ]
                ]
            )
        );

        $data['torrentUrl'] = $this->createTorrentUrl($object);
        $data['fileSize'] = $object->getFileLength();
        $data['magnetUri'] = $object->getMagnetUri();
        $data['isoPath'] = $this->createIsoPath($object);
        $data['isoUrl'] = $this->createIsoUrl($object);
        $data['isoSigUrl'] = $this->createIsoSigUrl($object);
        $data['fileName'] = $object->getFileName();
        $data['info'] = $this->purifier->purify($data['info']);
        $data['directoryUrl'] = $this->createDirectoryUrl($object);

        return $data;
    }

    private function createIsoPath(Release $release): string
    {
        return '/iso/' . $release->getVersion() . '/' . ($release->getFileName() ?: '');
    }

    private function createIsoUrl(Release $release): string
    {
        return $this->router->generate(
            'app_mirror_iso',
            [
                'file' => $release->getFileName(),
                'version' => $release->getVersion()
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    private function createDirectoryUrl(Release $release): string
    {
        return $this->router->generate(
            'app_mirror_iso',
            [
                'file' => '', // empty to link to directory
                'version' => $release->getVersion()
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    private function createIsoSigUrl(Release $release): ?string
    {
        // Signatures were introduced with version 2012.07.15
        return $release->getFileName() && $release->getReleaseDate() >= new \DateTime('2012-07-15')
            ? $this->createIsoUrl($release) . '.sig'
            : null;
    }

    private function createTorrentUrl(Release $release): ?string
    {
        return $release->getTorrentUrl() ? 'https://archlinux.org' . $release->getTorrentUrl() : null;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
