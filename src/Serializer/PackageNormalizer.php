<?php

namespace App\Serializer;

use App\Entity\Packages\Package;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class PackageNormalizer implements NormalizerInterface
{
    /** @var UrlGeneratorInterface */
    private $router;

    /** @var ObjectNormalizer */
    private $normalizer;

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
        return $data instanceof Package;
    }

    /**
     * @param Package $object
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
                        'repository',
                        'architecture',
                        'name',
                        'version',
                        'description',
                        'buildDate',
                        'groups'
                    ]
                ]
            )
        );
        $data['url'] = $this->router->generate(
            'app_packagedetails_index',
            [
                'arch' => $object->getRepository()->getArchitecture(),
                'pkgname' => $object->getName(),
                'repo' => $object->getRepository()->getName()
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        return $data;
    }
}
