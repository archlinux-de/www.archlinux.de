<?php

namespace App\Serializer;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class PaginatorNormalizer implements NormalizerInterface, NormalizerAwareInterface, CacheableSupportsMethodInterface
{
    private NormalizerInterface $normalizer;

    /**
     * @param Paginator $object
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        $objectIterator = $object->getIterator();
        return [
            'offset' => $object->getQuery()->getFirstResult(),
            'limit' => $object->getQuery()->getMaxResults(),
            'total' => $object->count(),
            'count' => $objectIterator->count(),
            'items' => $this->normalizer->normalize($objectIterator, $format, $context)
        ];
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof Paginator && $format == 'json';
    }

    public function setNormalizer(NormalizerInterface $normalizer): void
    {
        $this->normalizer = $normalizer;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
