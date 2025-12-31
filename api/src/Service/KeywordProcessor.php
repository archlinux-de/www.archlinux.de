<?php

namespace App\Service;

use App\Dto\AppStreamDataComponentDto;

class KeywordProcessor
{
    public function generatePackageKeywords(AppStreamDataComponentDto $appStreamDataComponentDto): array
    {
        $cleanedDescription = $this->cleanAppStreamDescription($appStreamDataComponentDto->getDescription());
        $appDataCategories = implode(' ', $appStreamDataComponentDto->getCategories());
        $appDataKeywords = implode( ' ', $appStreamDataComponentDto->getKeywords());

        $keywords = $cleanedDescription . ' ' . $appDataCategories . ' ' . $appDataKeywords;

        return $this->deduplicateWords($keywords);

    }
    private function cleanAppStreamDescription(string $uncleanDescription): string
    {
        $clean = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $uncleanDescription);
        if (empty($clean)) {
            return '';
        }
        $clean = mb_strtolower($clean, 'UTF-8');

        $words = explode(' ', $clean);
        $filteredWords = array_filter($words, function (string $word): bool {
            $stopWords = [
                'the', 'and', 'for', 'with', 'from', 'this', 'that', 'your', 'have', 'a', 'an', 'to', 'of', 'if', 'on',
                'is', 'as', 'it', 'you', 'in', 'all', 'at', 'der', 'die', 'das', 'und', 'ein', 'eine', 'mit',
                'von', 'für', 'ist', 'auf', 'sie'
            ];
            $word = trim($word);
            return !in_array($word, $stopWords);
        });

        return implode(' ', $filteredWords);
    }

    /**
     * @return array<string>
     * @throws \RuntimeException
     */
    private function deduplicateWords(string $text): array
    {
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        if ($words !== false) {
            return array_unique($words);
        }

        throw new \RuntimeException('Failed deduplicating words in KeywordsCleaner.php');
    }
}
