<?php

namespace App\Tests\Service;

use App\Entity\NewsItem;
use App\Service\NewsItemFetcher;
use App\Service\NewsItemSlugger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class NewsItemFetcherTest extends TestCase
{
    public function testFetchNewsItems()
    {
        $responseMock = new MockResponse(
            '<?xml version="1.0" encoding="utf-8"?>
                <feed xmlns="http://www.w3.org/2005/Atom">
                    <entry>
                        <title type="html"><![CDATA[Test Title]]></title>
                        <link rel="alternate" href="https://127.0.0.1/news/1.html"/>
                        <summary type="html"><![CDATA[Item Summary]]></summary>
                        <author>
                            <name><![CDATA[Author Name]]></name>
                            <uri>https://127.0.0.1/author/1</uri>
                        </author>
                        <updated>2018-02-22T19:06:26Z</updated>
                        <id>https://127.0.0.1/news/1</id>
                    </entry>
                </feed>'
        );
        $httpClient = new MockHttpClient($responseMock);

        /** @var NewsItemSlugger|MockObject $slugger */
        $slugger = $this->createMock(NewsItemSlugger::class);
        $slugger->expects($this->once())->method('slugify')->willReturn('slug');

        $newsItemFetcher = new NewsItemFetcher('http://foo', $slugger, $httpClient);

        /** @var NewsItem[] $newsItems */
        $newsItems = iterator_to_array($newsItemFetcher);
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

    public function testExceptionOnEmptyResponse()
    {
        $httpClient = new MockHttpClient(new MockResponse(''));

        /** @var NewsItemSlugger|MockObject $slugger */
        $slugger = $this->createMock(NewsItemSlugger::class);
        $slugger->expects($this->never())->method('slugify');

        $newsItemFetcher = new NewsItemFetcher('http://foo', $slugger, $httpClient);

        $this->expectException(\RuntimeException::class);
        iterator_to_array($newsItemFetcher);
    }

    public function testExceptionOnInvalidResponse()
    {
        $httpClient = new MockHttpClient(new MockResponse('foo'));

        /** @var NewsItemSlugger|MockObject $slugger */
        $slugger = $this->createMock(NewsItemSlugger::class);
        $slugger->expects($this->never())->method('slugify');

        $newsItemFetcher = new NewsItemFetcher('http://foo', $slugger, $httpClient);

        $this->expectException(\Exception::class);
        iterator_to_array($newsItemFetcher);
    }

    public function testExceptionOnIncompleteResponse()
    {
        $httpClient = new MockHttpClient(
            new MockResponse(
                '<?xml version="1.0" encoding="utf-8"?>
                <feed xmlns="http://www.w3.org/2005/Atom">
                    <entry></entry>
                </feed>'
            )
        );

        /** @var NewsItemSlugger|MockObject $slugger */
        $slugger = $this->createMock(NewsItemSlugger::class);
        $slugger->expects($this->never())->method('slugify');

        $newsItemFetcher = new NewsItemFetcher('http://foo', $slugger, $httpClient);

        $this->expectException(\Throwable::class);
        iterator_to_array($newsItemFetcher);
    }
}
