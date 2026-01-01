<?php

namespace App\Tests\Service;

use App\Dto\AppStreamDataComponentDto;
use App\Service\KeywordProcessor;
use PHPUnit\Framework\TestCase;

class KeywordProcessorTest extends TestCase
{
    public function testgeneratePackageKeywords(): void
    {
        $keywordProcessor = new KeywordProcessor();
        $testDescription = '
            AbiWord is a free word processing program. It is suitable for a
            wide variety of word processing tasks but remain focused on word
            processing. AbiWord is meant to remain relatively lightweight and support
            many file formats.
        ';
        $testCategories = ['office', 'wordprocessor'];
        $testKeywords = [];
        $testAppStreamDataDto = new AppStreamDataComponentDto(
            'abiword',
            $testCategories,
            $testKeywords,
            $testDescription,
        );
        $unsorted = $keywordProcessor->generatePackageKeywords($testAppStreamDataDto);
        $keywords = sort($unsorted, SORT_STRING);
        $unsortedExpected = [
            'abiword',
            'free',
            'word',
            'processing',
            'program',
            'suitable',
            'wide',
            'variety',
            'tasks',
            'but',
            'remain',
            'focused',
            'meant',
            'relatively',
            'lightweight',
            'support',
            'many',
            'file',
            'formats',
            'office',
            'wordprocessor'];
        $expectedKeywords = sort($unsortedExpected, SORT_STRING);

        $this->assertEquals($expectedKeywords, $keywords);
    }
}
