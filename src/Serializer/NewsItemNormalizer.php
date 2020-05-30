<?php

namespace App\Serializer;

use App\Entity\NewsItem;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\String\Slugger\SluggerInterface;

class NewsItemNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    /** @var ObjectNormalizer */
    private $normalizer;

    /** @var SluggerInterface */
    private $slugger;

    /** @var \HTMLPurifier */
    private $newsPurifier;

    /**
     * @param ObjectNormalizer $normalizer
     * @param SluggerInterface $slugger
     * @param \HTMLPurifier $newsPurifier
     */
    public function __construct(ObjectNormalizer $normalizer, SluggerInterface $slugger, \HTMLPurifier $newsPurifier)
    {
        $this->normalizer = $normalizer;
        $this->slugger = $slugger;
        $this->newsPurifier = $newsPurifier;
    }

    /**
     * @inheritDoc
     */
    public function supportsNormalization($data, string $format = null)
    {
        return $data instanceof NewsItem && $format == 'json';
    }

    /**
     * @param NewsItem $object
     * @param string $format
     * @param array<mixed> $context
     * @return array<mixed>
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        /** @var array<mixed> $data */
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

        $data['description'] = $this->newsPurifier->purify($data['description']);
        $data['slug'] = $this->slugger->slug($object->getTitle());

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
