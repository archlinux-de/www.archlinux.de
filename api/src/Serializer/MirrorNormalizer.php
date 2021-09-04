<?php

namespace App\Serializer;

use App\Entity\Mirror;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class MirrorNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    public function __construct(private ObjectNormalizer $normalizer)
    {
    }

    /**
     * @param Mirror $object
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
                        'url',
                        'protocol',
                        'country',
                        'lastSync',
                        'delay',
                        'durationAvg',
                        'score',
                        'completionPct',
                        'durationStddev',
                        'ipv4',
                        'ipv6'
                    ]
                ]
            )
        );

        $data['host'] = parse_url($data['url'], PHP_URL_HOST);

        return $data;
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof Mirror && $format == 'json';
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
