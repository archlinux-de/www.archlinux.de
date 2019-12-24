<?php

namespace App\Serializer;

use App\Entity\Packages\Repository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class RepositoryNormalizer implements NormalizerInterface
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
        return $data instanceof Repository;
    }

    /**
     * @param Repository $object
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
                        'name',
                        'architecture',
                        'testing'
                    ]
                ]
            )
        );
        $data['url'] = $this->router->generate(
            'app_packages_index',
            [
                'repository' => $object->getName(),
                'architecture' => $object->getArchitecture()
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        return $data;
    }
}
