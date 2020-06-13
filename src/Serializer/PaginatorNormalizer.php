<?php

namespace App\Serializer;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class PaginatorNormalizer implements NormalizerInterface, NormalizerAwareInterface, CacheableSupportsMethodInterface
{
    /** @var NormalizerInterface */
    private $normalizer;

    /**
     * @param Paginator $object
     * @param string|null $format
     * @param array $context
     * @return array
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        /** @var \ArrayIterator<int, object> $objectIterator */
        $objectIterator = $object->getIterator();
        return [
            'offset' => $object->getQuery()->getFirstResult(),
            'limit' => $object->getQuery()->getMaxResults(),
            'total' => $object->count(),
            'count' => $objectIterator->count(),
            'items' => $this->normalizer->normalize($objectIterator, $format, $context)
        ];
    }

    /**
     * @param mixed $data
     * @param string|null $format
     * @return bool
     */
    public function supportsNormalization($data, string $format = null)
    {
        return $data instanceof Paginator && $format == 'json';
    }

    /**
     * @param NormalizerInterface $normalizer
     */
    public function setNormalizer(NormalizerInterface $normalizer): void
    {
        $this->normalizer = $normalizer;
    }

    /**
     * @return bool
     */
    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
