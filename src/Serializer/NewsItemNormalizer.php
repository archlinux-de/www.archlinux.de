<?php

namespace App\Serializer;

use App\Entity\NewsItem;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\String\Slugger\SluggerInterface;

class NewsItemNormalizer implements NormalizerInterface
{
    /** @var UrlGeneratorInterface */
    private $router;

    /** @var ObjectNormalizer */
    private $normalizer;

    /** @var SluggerInterface */
    private $slugger;

    /**
     * @param UrlGeneratorInterface $router
     * @param ObjectNormalizer $normalizer
     * @param SluggerInterface $slugger
     */
    public function __construct(UrlGeneratorInterface $router, ObjectNormalizer $normalizer, SluggerInterface $slugger)
    {
        $this->router = $router;
        $this->normalizer = $normalizer;
        $this->slugger = $slugger;
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
                'id' => $object->getId(),
                'slug' => $this->slugger->slug($object->getTitle())
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        return $data;
    }
}
