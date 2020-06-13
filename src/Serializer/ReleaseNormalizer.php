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
                        'isoUrl',
                        'kernelVersion',
                        'releaseDate',
                        'sha1Sum'
                    ]
                ]
            )
        );

        $data['torrentUrl'] = $object->getTorrent()->getUrl()
            ? 'https://www.archlinux.org' . $object->getTorrent()->getUrl()
            : null;
        $data['fileSize'] = $object->getTorrent()->getFileLength();
        $data['magnetUri'] = $object->getTorrent()->getMagnetUri();
        $data['isoPath'] = $data['isoUrl'];
        $data['isoUrl'] = $data['available'] ? $this->router->generate(
            'app_mirror_iso',
            [
                'file' => $object->getTorrent()->getFileName(),
                'version' => $object->getVersion()
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        ) : null;
        $data['isoSigUrl'] = 'https://www.archlinux.org' . $data['isoPath'] . '.sig';
        $data['fileName'] = $object->getTorrent()->getFileName();
        $data['info'] = $this->releasePurifier->purify($data['info']);

        return $data;
    }

    /**
     * @return bool
     */
    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
