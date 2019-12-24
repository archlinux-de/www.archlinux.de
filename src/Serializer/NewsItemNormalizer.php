<?php

namespace App\Serializer;

use App\Entity\NewsItem;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class NewsItemNormalizer implements NormalizerInterface
{
    /** @var UrlGeneratorInterface */
    private $router;

    /** @var ObjectNormalizer */
    private $normalizer;

    /**
     * @param UrlGeneratorInterface $router
     * @param ObjectNormalizer $normalizer
     */
    public function __construct(UrlGeneratorInterface $router, ObjectNormalizer $normalizer)
    {
        $this->router = $router;
        $this->normalizer = $normalizer;
    }

    /**
     * @inheritDoc
     */
    public function supportsNormalization($data, string $format = null)
    {
        return $data instanceof NewsItem;
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
                        'title',
                        'author',
                        'lastModified'
                    ]
                ]
            )
        );
        $data['url'] = $this->router->generate(
            'app_news_item',
            [
                'slug' => $object->getSlug(),
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        return $data;
    }
}
