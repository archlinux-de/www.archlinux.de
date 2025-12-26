<?php

namespace App\Serializer;

use App\Entity\NewsItem;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\String\Slugger\SluggerInterface;

class NewsItemNormalizer implements NormalizerInterface
{
    private readonly NormalizerInterface $normalizer;

    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')] NormalizerInterface $normalizer,
        private readonly SluggerInterface $slugger,
        private readonly HtmlSanitizerInterface $htmlSanitizer
    ) {
        assert($normalizer instanceof ObjectNormalizer);
        $this->normalizer = $normalizer;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof NewsItem && $format === 'json';
    }

    /**
     * @param NewsItem $object
     * @return mixed[]
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        /** @var mixed[] $data */
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

        $data['description'] = $this->htmlSanitizer->sanitize($data['description']);
        $data['slug'] = $this->slugger->slug($object->getTitle());

        return $data;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [NewsItem::class => $format === 'json'];
    }
}
