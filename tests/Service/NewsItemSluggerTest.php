<?php

namespace App\Tests\Service;

use App\Entity\NewsItem;
use App\Service\NewsItemSlugger;
use App\Service\Slugger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class NewsItemSluggerTest extends TestCase
{
    /**
     * @param string $id
     * @param string $expected
     * @dataProvider provideIdSlugs
     */
    public function testSlugifyId(string $id, string $expected)
    {
        $newsItem = new NewsItem($id);
        $newsItem->setTitle('Test Title');

        /** @var Slugger|MockObject $slugger */
        $slugger = $this->createMock(Slugger::class);
        $slugger
            ->expects($this->once())
            ->method('slugify')
            ->with('Test Title')
            ->willReturn('test-title');

        $newsItemSlugger = new NewsItemSlugger($slugger);

        $this->assertEquals($expected . '-test-title', $newsItemSlugger->slugify($newsItem));
    }

    public function testLimitSlugLength()
    {
        $newsItem = new NewsItem('1');
        $newsItem->setTitle('');

        /** @var Slugger|MockObject $slugger */
        $slugger = $this->createMock(Slugger::class);
        $slugger
            ->expects($this->once())
            ->method('slugify')
            ->willReturn(str_repeat('a', 254));

        $newsItemSlugger = new NewsItemSlugger($slugger);

        $this->assertEquals('1-' . str_repeat('a', 253), $newsItemSlugger->slugify($newsItem));
    }

    /**
     * @return array
     */
    public function provideIdSlugs(): array
    {
        return [
            ['https://bbs.archlinux.de/viewtopic.php?id=31780', '31780'],
            ['31780', '31780'],
            ['https://bbs.archlinux.de/viewtopic.php?id=abc', '1232878237'],
            ['abc', '891568578'],
            ['🐱', '2622111606'],
            ['', '0']
        ];
    }
}
