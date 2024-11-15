<?php

namespace App\Serializer;

use App\Entity\Country;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class CountryNormalizer implements NormalizerInterface
{
    private NormalizerInterface $normalizer;

    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')] NormalizerInterface $normalizer,
    ) {
        assert($normalizer instanceof ObjectNormalizer);
        $this->normalizer = $normalizer;
    }

    /**
     * @param Country $object
     * @return mixed[]
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        /** @var mixed[] $data */
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

    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof Country && $format === 'json';
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Country::class => $format === 'json'];
    }
}
