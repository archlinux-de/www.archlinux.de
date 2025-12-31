<?php

namespace App\Serializer;

use App\Dto\AppStreamDataComponentDto;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

readonly class AppStreamDataDenormalizer implements DenormalizerInterface
{

    /**
     * @return AppStreamDataComponentDto[]
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): array
    {
        var_dump($data);
        if (!isset($data['component'])) {
            throw new \RuntimeException('Invalid AppStreamData structure.');
        }

        return [
            ...(function () use ($data) {
                foreach ($data as $component) {
                    if (!isset($component['pkgname']) || !is_string($component['pkgname'])) {
                        continue;
                    }

                    yield $this->mapToMetaData($component);
                }
            })()
        ];
    }

    public function supportsDenormalization(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = []
    ): bool
    {
        return $type === AppStreamDataComponentDto::class . '[]';
    }

    public function getSupportedTypes(?string $format): array
    {
        return [AppStreamDataComponentDto::class . '[]' => true];
    }

    /**
     * @param array<string, mixed> $component
     */
    private function mapToMetaData(array $component): AppStreamDataComponentDto
    {

        $description = $this->parseAppStreamDescription($component);
        $categories = $this->getCategories($component);
        $keywords = $this->getKeywords($component);

        return new AppStreamDataComponentDto(
            $component['pkgname'],
            $categories,
            $keywords,
            $description
        );
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
     * @return array<string>
     */
    private function getCategories(array $component): array
    {
        if (!isset($component['categories']['category'])) {
            return [];
        }

        $categoryData = $component['categories']['category'];

        if (is_array($categoryData)) {
            return array_map(fn($cat) => mb_strtolower((string) $cat, 'UTF-8'), $categoryData);

        }

        return [mb_strtolower((string) $categoryData, 'UTF-8')];
    }

    /**
     * @param array<string, mixed> $component
     * @return array<string>
     */
    public function getKeywords(array $component): array
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

        return $allKeywords;
    }
}
