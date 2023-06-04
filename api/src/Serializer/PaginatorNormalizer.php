<?php

namespace App\Serializer;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class PaginatorNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    /**
     * @param Paginator $object
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        $objectIterator = $object->getIterator();
        assert($objectIterator instanceof \Countable);

        return [
            'offset' => $object->getQuery()->getFirstResult(),
            'limit' => $object->getQuery()->getMaxResults(),
            'total' => $object->count(),
            'count' => $objectIterator->count(),
            'items' => $this->normalizer->normalize($objectIterator, $format, $context)
        ];
    }

    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof Paginator && $format === 'json';
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Paginator::class => $format === 'json'];
    }
}
