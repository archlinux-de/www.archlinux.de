<?php

namespace App\Serializer;

use App\Entity\Packages\Repository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class RepositoryNormalizer implements NormalizerInterface
{
    private readonly NormalizerInterface $normalizer;

    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')] NormalizerInterface $normalizer,
    ) {
        assert($normalizer instanceof ObjectNormalizer);
        $this->normalizer = $normalizer;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Repository && $format === 'json';
    }

    /**
     * @param Repository $object
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
                        'name',
                        'architecture',
                        'testing'
                    ]
                ]
            )
        );

        return $data;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Repository::class => $format === 'json'];
    }
}
