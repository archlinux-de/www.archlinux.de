<?php

namespace App\Tests\Serializer;

use App\Entity\NewsItem;
use App\Serializer\NewsItemDenormalizer;
use PHPUnit\Framework\TestCase;

class NewsItemDenormalizerTest extends TestCase
{
    public function testDenormalize(): void
    {
        $newsItemDenormalizer = new NewsItemDenormalizer('https://forum.archlinux.de');
        /** @var NewsItem[] $newsItems */
        $newsItems = $newsItemDenormalizer->denormalize(
            [
                'data' => [
                    [
                        'id' => 1,
                        'attributes' => [
                            'title' => 'Test Title',
                            'slug' => '1-test-title',
                            'createdAt' => (new \DateTime('2018-02-22T19:06:26Z'))->format(\DATE_RFC3339)
                        ],
                        'relationships' => [
                            'user' => ['data' => ['id' => 123]],
                            'firstPost' => ['data' => ['id' => 1]]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'posts',
                        'id' => 1,
                        'attributes' => ['contentHtml' => 'Item Summary']
                    ],
                    [
                        'type' => 'users',
                        'id' => 123,
                        'attributes' => ['displayName' => 'Author Name', 'slug' => 'author']
                    ]
                ]
            ],
            NewsItem::class . '[]'
        );

        $this->assertCount(1, $newsItems);
        $this->assertEquals(1, $newsItems[0]->getId());
        $this->assertEquals(new \DateTime('2018-02-22T19:06:26Z'), $newsItems[0]->getLastModified());
        $this->assertEquals('Test Title', $newsItems[0]->getTitle());
        $this->assertEquals('https://forum.archlinux.de/d/1-test-title', $newsItems[0]->getLink());
        $this->assertEquals('Item Summary', $newsItems[0]->getDescription());
        $this->assertEquals('Author Name', $newsItems[0]->getAuthor()->getName());
        $this->assertEquals('https://forum.archlinux.de/u/author', $newsItems[0]->getAuthor()->getUri());
    }

    public function testSupportsDenormalization(): void
    {
        $newsItemDenormalizer = new NewsItemDenormalizer('');

        $this->assertTrue($newsItemDenormalizer->supportsDenormalization([], NewsItem::class . '[]'));
        $this->assertTrue($newsItemDenormalizer->hasCacheableSupportsMethod());
    }
}
