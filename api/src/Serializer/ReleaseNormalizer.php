<?php

namespace App\Serializer;

use App\Entity\Release;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class ReleaseNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    /** @var UrlGeneratorInterface */
    private $router;

    /** @var ObjectNormalizer */
    private $normalizer;

    /** @var \HTMLPurifier */
    private $releasePurifier;

    /**
     * @param UrlGeneratorInterface $router
     * @param ObjectNormalizer $normalizer
     * @param \HTMLPurifier $releasePurifier
     */
    public function __construct(
        UrlGeneratorInterface $router,
        ObjectNormalizer $normalizer,
        \HTMLPurifier $releasePurifier
    ) {
        $this->router = $router;
        $this->normalizer = $normalizer;
        $this->releasePurifier = $releasePurifier;
    }

    /**
     * @inheritDoc
     */
    public function supportsNormalization($data, string $format = null)
    {
        return $data instanceof Release && $format == 'json';
    }

    /**
     * @param Release $object
     * @param string $format
     * @param array $context
     * @return array
     */
    public function normalize($object, string $format = null, array $context = [])
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
        $data['fileSize'] = $object->getTorrent()->getFileLength();
        $data['magnetUri'] = $object->getTorrent()->getMagnetUri();
        $data['isoPath'] = $this->createIsoPath($object);
        $data['isoUrl'] = $this->createIsoUrl($object);
        $data['isoSigUrl'] = $this->createIsoSigUrl($object);
        $data['fileName'] = $object->getTorrent()->getFileName();
        $data['info'] = $this->releasePurifier->purify($data['info']);

        return $data;
    }

    private function createIsoPath(Release $release): string
    {
        return '/iso/' . $release->getVersion() . '/' . ($release->getTorrent()->getFileName() ?: '');
    }

    private function createIsoUrl(Release $release): string
    {
        // torrent-filename ab 2010.05
        return $this->router->generate(
            'app_mirror_iso',
            [
                'file' => $release->getTorrent()->getFileName(),
                'version' => $release->getVersion()
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    private function createIsoSigUrl(Release $release): ?string
    {
        // Erst ab 2012.07.15
        return $release->getTorrent()->getFileName() && $release->getReleaseDate() >= new \DateTime('2012-07-15')
            ? $this->createIsoUrl($release) . '.sig'
            : null;
    }

    private function createTorrentUrl(Release $release): ?string
    {
        return $release->getTorrent()->getUrl() ? 'https://archlinux.org' . $release->getTorrent()->getUrl() : null;
    }

    /**
     * @return bool
     */
    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
