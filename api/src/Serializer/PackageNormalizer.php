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
    public function __construct(
        private UrlGeneratorInterface $router,
        private ObjectNormalizer $normalizer,
        private string $cgitUrl
    ) {
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof Package && $format == 'json';
    }

    /**
     * @param Package $object
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        /** @var array $data */
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
                        'popularity'
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
        $data['sourceChangelogUrl'] = $cgitLink . 'log/trunk?h=packages/' . $object->getBase();

        return $data;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
