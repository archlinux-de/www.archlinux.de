<?php

namespace App\Serializer;

use App\Entity\Packages\Metadata;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class AppStreamDataDenormalizer
{
    private Serializer $serializer;

    public function __construct()
    {
        $encoder = new XmlEncoder();
        $normalizer = new ObjectNormalizer();

        $this->serializer = new Serializer([$normalizer], [$encoder]);
    }

    /**
     * @return \Traversable<string, Metadata>
     */
    public function denormalize(string $xml): \Traversable
    {
        $data = $this->parseXml($xml);
        if (empty($data)) {
            return;
        }

        foreach ($data as $component) {
            if (!isset($component['pkgname']) || !is_string($component['pkgname'])) {
                continue;
            }

            yield $component['pkgname'] => $this->mapToMetaData($component);
        }
    }

    /**
     * @return array<array<string, mixed>>
     */
    private function parseXml(string $xmlContent): array
    {
        $data = $this->serializer->decode($xmlContent, 'xml');

        if (!isset($data['components']['component'])) {
            throw new \RuntimeException('Invalid AppStreamData structure.');
        }

        return (array) $data['components']['component'];
    }

    /**
     * @param array<string, mixed> $component
     */
    private function mapToMetaData(array $component): Metadata
    {
        $metaData = new Metadata();
        $metaData->setType($component['@type']);
        $metaData->setName($component['name']);
        $metaData->setGermanDescription($component['description']['p']);

        $categories = isset($component['categories']['category'])
            ? implode(',', (array) $component['categories']['category'])
            : '';

        $metaData->setCategories($categories);

        return $metaData;
    }
}
