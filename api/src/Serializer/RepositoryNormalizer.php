<?php

namespace App\Serializer;

use App\Entity\Packages\Repository;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class RepositoryNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    public function __construct(private ObjectNormalizer $normalizer)
    {
    }

    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof Repository && $format == 'json';
    }

    /**
     * @param Repository $object
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        /** @var array $data */
        $data = $this->normalizer->normalize(
            $object,
            $format,
            array_merge(
                $context,
                [
                    AbstractNormalizer::ATTRIBUTES => [
                        'name',
                        'architecture',
                        'testing'
                    ]
                ]
            )
        );

        return $data;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
