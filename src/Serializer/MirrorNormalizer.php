<?php

namespace App\Serializer;

use App\Entity\Mirror;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class MirrorNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    /** @var ObjectNormalizer */
    private $normalizer;

    /**
     * @param ObjectNormalizer $normalizer
     */
    public function __construct(ObjectNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    /**
     * @param Mirror $object
     * @param string|null $format
     * @param array<mixed> $context
     * @return array<mixed>
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        /** @var array<mixed> $data */
        $data = $this->normalizer->normalize(
            $object,
            $format,
            array_merge(
                $context,
                [
                    AbstractNormalizer::ATTRIBUTES => [
                        'url',
                        'protocol',
                        'country',
                        'lastSync',
                        'delay',
                        'durationAvg',
                        'score',
                        'completionPct',
                        'durationStddev',
                        'isos',
                        'ipv4',
                        'ipv6',
                        'active'
                    ]
                ]
            )
        );

        $data['host'] = parse_url($data['url'], PHP_URL_HOST);

        return $data;
    }

    /**
     * @param object $data
     * @param string|null $format
     * @return bool
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof Mirror;
    }

    /**
     * @return bool
     */
    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
