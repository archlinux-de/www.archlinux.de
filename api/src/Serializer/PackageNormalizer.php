<?php

namespace App\Serializer;

use App\Entity\Packages\Package;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class PackageNormalizer implements NormalizerInterface
{
    private NormalizerInterface $normalizer;

    public function __construct(
        private readonly UrlGeneratorInterface $router,
        #[Autowire(service: 'serializer.normalizer.object')] NormalizerInterface $normalizer,
        private readonly string $gitlabUrl
    ) {
        assert($normalizer instanceof ObjectNormalizer);
        $this->normalizer = $normalizer;
    }

    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof Package && $format === 'json';
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

        $data['sourceUrl'] = $this->createGitlabLink('tree', $object);
        $data['sourceChangelogUrl'] = $this->createGitlabLink('commits', $object);

        return $data;
    }

    private function createGitlabLink(string $type, Package $package): string
    {
        assert(in_array($type, ['tree', 'commits']));

        return sprintf(
            '%s/%s/-/%s/%s',
            $this->gitlabUrl,
            $this->createGitlabPath($package->getBase()),
            $type,
            $this->createGitlabTag($package->getVersion())
        );
    }

    private function createGitlabPath(string $name): string
    {
        // see https://github.com/archlinux/archweb/blob/master/main/utils.py#L139-L148
        $replaces = [
            '/([a-zA-Z0-9]+)\+([a-zA-Z]+)/' => '$1-$2',
            '/\+/' => 'plus',
            '/[^a-zA-Z0-9_\-\.]/' => '-',
            '/[_\-]{2,}/' => '-',
            '/^tree$/' => 'unix-tree'
        ];

        return (string)preg_replace(array_keys($replaces), array_values($replaces), $name);
    }

    private function createGitlabTag(string $version): string
    {
        return (string)preg_replace('/[^a-zA-Z0-9_\-\.\+]+/', '-', $version);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Package::class => $format === 'json'];
    }
}
