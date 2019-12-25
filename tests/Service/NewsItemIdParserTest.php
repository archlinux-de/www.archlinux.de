<?php

namespace App\Tests\Service;

use App\Service\NewsItemIdParser;
use PHPUnit\Framework\TestCase;

class NewsItemIdParserTest extends TestCase
{
    /**
     * @param string $input
     * @param int $expected
     * @dataProvider provideIds
     */
    public function testParseId(string $input, int $expected): void
    {
        $newsItemIdParser = new NewsItemIdParser();
        $this->assertEquals($expected, $newsItemIdParser->parseId($input));
    }

    /**
     * @return array<array>
     */
    public function provideIds(): array
    {
        return [
            ['1', 1],
            ['http://localhost/news?id=2', 2],
            ['http://localhost/news/3', 1730349168]
        ];
    }
}
