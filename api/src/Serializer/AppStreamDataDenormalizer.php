<?php

namespace App\Serializer;

use App\Service\KeywordsCleaner;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

readonly class AppStreamDataDenormalizer
{
    private Serializer $serializer;
    private KeywordsCleaner $keywordCleaner;

    public function __construct(KeywordsCleaner $keywordCleaner)
    {
        $encoder = new XmlEncoder();
        $normalizer = new ObjectNormalizer();

        $this->serializer = new Serializer([$normalizer], [$encoder]);

        $this->keywordCleaner = $keywordCleaner;
    }

    /**
     * @return \Traversable<string, array<string>>
     */
    public function denormalize(string $xml): \Traversable
    {
        $data = $this->parseXml($xml);

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

        if (!isset($data['component'])) {
            throw new \RuntimeException('Invalid AppStreamData structure.');
        }

        return (array) $data['component'];
    }

    /**
     * @param array<string, mixed> $component
     * @return array<string>
     */
    private function mapToMetaData(array $component): array
    {

        $description = $this->parseAppStreamDescription($component);
        $categories = $this->getCategories($component);
        $keywords = $this->getKeywords($component);

        $cleanDescription =
            $this->keywordCleaner->cleanAppStreamDescription($description) .
            ' ' .
            $categories .
            ' ' .
            $keywords;

        return $this->keywordCleaner->deduplicateWords($cleanDescription);
    }

    /**
     * @param array<string, mixed> $component
     */
    private function parseAppStreamDescription(array $component): string
    {
        if (!isset($component['description'])) {
            return '';
        }

        $descriptionData = $component['description'];
        $allText = [];

        $blocks = isset($descriptionData[0]) ? $descriptionData : [$descriptionData];

        if (is_array($blocks)) {
            foreach ($blocks as $block) {
                $lang = $block['@xml:lang'] ?? null;

                if ($lang === null || in_array($lang, ['en', 'de'])) {
                    array_walk_recursive($block, function ($value, $key) use (&$allText): void {
                        // Grab all strings, skip keys starting with @ (attributes)
                        //@phpstan-ignore-next-line cast.string
                        if (is_string($value) && !str_starts_with((string)$key, '@')) {
                            $allText[] = $value;
                        }
                    });
                }
            }
        }

        return implode(' ', $allText);
    }

    /**
     *
     * Obtain array of categories per component.
     * Categories are not localized (i.e. English only).
     * @see https://www.freedesktop.org/software/appstream/docs/chap-CatalogData.html#tag-ct-categories
     *
     * @param array<string, mixed> $component
     */
    private function getCategories(array $component): string
    {
        if (!isset($component['categories']['category'])) {
            return '';
        }

        $categoryData = $component['categories']['category'];

        // If it's an array, lowercase every element before imploding
        if (is_array($categoryData)) {
            //@phpstan-ignore-next-line cast.string
            $lowercasedCategories = array_map(fn($cat) => mb_strtolower((string) $cat, 'UTF-8'), $categoryData);
            return implode(' ', $lowercasedCategories);
        }

        // If it's a single string, just lowercase it
        //@phpstan-ignore-next-line cast.string
        return mb_strtolower((string) $categoryData, 'UTF-8');
    }

    /**
     * @param array<string, mixed> $component
     */
    public function getKeywords(array $component): string
    {
        $allKeywords = [];
        $keywordData = $component['keywords'] ?? [];

        $blocks = isset($keywordData[0]) ? $keywordData : [$keywordData];

        if (is_array($blocks)) {
            foreach ($blocks as $block) {
                $lang = $block['@xml:lang'] ?? null;

                if (($lang === null || $lang === 'de') && isset($block['keyword'])) {
                    $list = is_array($block['keyword']) ? $block['keyword'] : [$block['keyword']];
                    foreach ($list as $word) {
                        if (is_string($word)) {
                            $allKeywords[] = mb_strtolower($word, 'UTF-8');
                        }
                    }
                }
            }
        }

        return implode(' ', $allKeywords);
    }
}
