<?php

namespace App\Serializer;

use App\Entity\Packages\Package;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class PackageNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    /** @var UrlGeneratorInterface */
    private $router;

    /** @var ObjectNormalizer */
    private $normalizer;

    /** @var string */
    private $cgitUrl;

    public function __construct(UrlGeneratorInterface $router, ObjectNormalizer $normalizer, string $cgitUrl)
    {
        $this->router = $router;
        $this->normalizer = $normalizer;
        $this->cgitUrl = $cgitUrl;
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
                        'fileName',
                        'name',
                        'base',
                        'version',
                        'description',
                        'groups',
                        'compressedSize',
                        'installedSize',
                        'sha256sum',
                        'url',
                        'licenses',
                        'architecture',
                        'buildDate',
                        'packager',
                        'replacements',
                        'conflicts',
                        'provisions',
                        'dependencies',
                        'optionalDependencies',
                        'makeDependencies',
                        'checkDependencies',
                    ]
                ]
            )
        );

        $data['packageUrl'] = $this->router->generate(
            'app_mirror_package',
            [
                'architecture' => $object->getRepository()->getArchitecture(),
                'file' => $object->getFileName(),
                'repository' => $object->getRepository()->getName()
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $cgitLink = $this->cgitUrl . (
            in_array(
                $object->getRepository()->getName(),
                [
                    'community',
                    'community-testing',
                    'multilib',
                    'multilib-testing',
                ]
            ) ? 'community' : 'packages'
            )
            . '.git/';

        $data['sourceUrl'] = $cgitLink . 'tree/trunk?h=packages/' . $object->getBase();
        $data['sourceChangelogUrl'] = $cgitLink . 'tree/trunk?h=packages/' . $object->getBase();

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
