<?php

namespace App\Tests\Service;

use App\Entity\NewsItem;
use App\Service\NewsItemFetcher;
use App\Service\NewsItemSlugger;
use FeedIo\FeedIo;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class NewsItemFetcherTest extends TestCase
{
    public function testFetchNewsItems()
    {
        $guzzleMock = new MockHandler([
            new Response(
                200,
                [],
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
            )
        ]);
        $guzzleHhandler = HandlerStack::create($guzzleMock);
        $guzzleClient = new Client(['handler' => $guzzleHhandler]);

        /** @var NewsItemSlugger|MockObject $slugger */
        $slugger = $this->createMock(NewsItemSlugger::class);
        $slugger->expects($this->once())->method('slugify')->willReturn('slug');

        $feedIo = new FeedIo(new \FeedIo\Adapter\Guzzle\Client($guzzleClient), new NullLogger());
        $newsItemFetcher = new NewsItemFetcher($feedIo, '', $slugger);

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
        $guzzleMock = new MockHandler([
            new Response()
        ]);
        $guzzleHhandler = HandlerStack::create($guzzleMock);
        $guzzleClient = new Client(['handler' => $guzzleHhandler]);

        /** @var NewsItemSlugger|MockObject $slugger */
        $slugger = $this->createMock(NewsItemSlugger::class);
        $slugger->expects($this->never())->method('slugify');

        $feedIo = new FeedIo(new \FeedIo\Adapter\Guzzle\Client($guzzleClient), new NullLogger());
        $newsItemFetcher = new NewsItemFetcher($feedIo, '', $slugger);

        $this->expectException(\RuntimeException::class);
        iterator_to_array($newsItemFetcher);
    }

    public function testExceptionOnInvalidResponse()
    {
        $guzzleMock = new MockHandler([
            new Response(200, [], 'foo')
        ]);
        $guzzleHhandler = HandlerStack::create($guzzleMock);
        $guzzleClient = new Client(['handler' => $guzzleHhandler]);

        /** @var NewsItemSlugger|MockObject $slugger */
        $slugger = $this->createMock(NewsItemSlugger::class);
        $slugger->expects($this->never())->method('slugify');

        $feedIo = new FeedIo(new \FeedIo\Adapter\Guzzle\Client($guzzleClient), new NullLogger());
        $newsItemFetcher = new NewsItemFetcher($feedIo, '', $slugger);

        $this->expectException(\RuntimeException::class);
        iterator_to_array($newsItemFetcher);
    }

    public function testExceptionOnIncompleteResponse()
    {
        $guzzleMock = new MockHandler([
            new Response(
                200,
                [],
                '<?xml version="1.0" encoding="utf-8"?>
                <feed xmlns="http://www.w3.org/2005/Atom">
                    <entry></entry>
                </feed>'
            )
        ]);
        $guzzleHhandler = HandlerStack::create($guzzleMock);
        $guzzleClient = new Client(['handler' => $guzzleHhandler]);

        /** @var NewsItemSlugger|MockObject $slugger */
        $slugger = $this->createMock(NewsItemSlugger::class);
        $slugger->expects($this->never())->method('slugify');

        $feedIo = new FeedIo(new \FeedIo\Adapter\Guzzle\Client($guzzleClient), new NullLogger());
        $newsItemFetcher = new NewsItemFetcher($feedIo, '', $slugger);

        $this->expectException(\RuntimeException::class);
        iterator_to_array($newsItemFetcher);
    }
}
