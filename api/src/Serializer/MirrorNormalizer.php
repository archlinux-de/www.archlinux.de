<?php

namespace App\Serializer;

use App\Entity\Mirror;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class MirrorNormalizer implements NormalizerInterface
{
    private NormalizerInterface $normalizer;

    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')] NormalizerInterface $normalizer,
    ) {
        assert($normalizer instanceof ObjectNormalizer);
        $this->normalizer = $normalizer;
    }

    /**
     * @param Mirror $object
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
                        'url',
                        'country',
                        'lastSync',
                        'delay',
                        'durationAvg',
                        'score',
                        'completionPct',
                        'durationStddev',
                        'ipv4',
                        'ipv6',
                        'popularity'
                    ]
                ]
            )
        );

        $data['host'] = parse_url($data['url'], PHP_URL_HOST);

        return $data;
    }

    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof Mirror && $format === 'json';
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Mirror::class => $format === 'json'];
    }
}
