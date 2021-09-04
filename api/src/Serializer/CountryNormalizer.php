<?php

namespace App\Serializer;

use App\Entity\Country;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class CountryNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    public function __construct(private ObjectNormalizer $normalizer)
    {
    }

    /**
     * @param Country $object
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
                        'code',
                        'name'
                    ]
                ]
            )
        );

        return $data;
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof Country && $format == 'json';
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
