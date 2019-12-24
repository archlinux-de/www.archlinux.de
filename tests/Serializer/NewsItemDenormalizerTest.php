<?php

namespace App\Tests\Serializer;

use App\Entity\NewsItem;
use App\Serializer\NewsItemDenormalizer;
use App\Service\NewsItemSlugger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class NewsItemDenormalizerTest extends TestCase
{
    public function testDenormalize(): void
    {
        /** @var NewsItemSlugger|MockObject $newsItemSlugger */
        $newsItemSlugger = $this->createMock(NewsItemSlugger::class);
        $newsItemSlugger
            ->expects($this->once())
            ->method('slugify')
            ->willReturn('slug');

        $newsItemDenormalizer = new NewsItemDenormalizer($newsItemSlugger);
        /** @var NewsItem[] $newsItems */
        $newsItems = $newsItemDenormalizer->denormalize(
            [
                'entry' => [
                    [
                        'id' => 'https://127.0.0.1/news/1',
                        'title' => ['#' => 'Test Title'],
                        'link' => ['@href' => 'https://127.0.0.1/news/1.html'],
                        'summary' => ['#' => 'Item Summary'],
                        'author' => [
                            'name' => 'Author Name',
                            'uri' => 'https://127.0.0.1/author/1'
                        ],
                        'updated' => '2018-02-22T19:06:26Z'
                    ]
                ]
            ],
            NewsItem::class . '[]'
        );

        $this->assertCount(1, $newsItems);
        $this->assertEquals('https://127.0.0.1/news/1', $newsItems[0]->getId());
        $this->assertEquals('slug', $newsItems[0]->getSlug());
        $this->assertEquals(new \DateTime('2018-02-22T19:06:26Z'), $newsItems[0]->getLastModified());
        $this->assertEquals('Test Title', $newsItems[0]->getTitle());
        $this->assertEquals('https://127.0.0.1/news/1.html', $newsItems[0]->getLink());
        $this->assertEquals('Item Summary', $newsItems[0]->getDescription());
        $this->assertEquals('Author Name', $newsItems[0]->getAuthor()->getName());
        $this->assertEquals('https://127.0.0.1/author/1', $newsItems[0]->getAuthor()->getUri());
    }

    public function testSupportsDenormalization(): void
    {
        $newsItemDenormalizer = new NewsItemDenormalizer($this->createMock(NewsItemSlugger::class));

        $this->assertTrue($newsItemDenormalizer->supportsDenormalization(['releases' => []], NewsItem::class . '[]'));
    }
}
