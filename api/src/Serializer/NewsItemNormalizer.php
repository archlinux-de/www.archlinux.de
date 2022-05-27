<?php

namespace App\Serializer;

use App\Entity\NewsItem;
use HTMLPurifier;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\String\Slugger\SluggerInterface;

class NewsItemNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    public function __construct(
        private ObjectNormalizer $normalizer,
        private SluggerInterface $slugger,
        private HTMLPurifier $purifier
    ) {
    }

    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof NewsItem && $format == 'json';
    }

    /**
     * @param NewsItem $object
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
                        'id',
                        'title',
                        'link',
                        'author',
                        'lastModified',
                        'description'
                    ]
                ]
            )
        );

        $data['description'] = $this->purifier->purify($data['description']);
        $data['slug'] = $this->slugger->slug($object->getTitle());

        return $data;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
