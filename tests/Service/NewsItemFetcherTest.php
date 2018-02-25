<?php

namespace App\Tests\Service;

use App\Service\NewsItemFetcher;
use FeedIo\FeedIo;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class NewsItemFetcherTest extends TestCase
{
    public function testFetchNewsItems()
    {
        $guzzleMock = new MockHandler([
            new Response(200, [], '<?xml version="1.0" encoding="utf-8"?>
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
                </feed>
                ')
        ]);
        $guzzleHhandler = HandlerStack::create($guzzleMock);
        $guzzleClient = new Client(['handler' => $guzzleHhandler]);

        $feedIo = new FeedIo(new \FeedIo\Adapter\Guzzle\Client($guzzleClient), new NullLogger());
        $newsItemFetcher = new NewsItemFetcher($feedIo, '');

        $newsItems = $newsItemFetcher->fetchNewsItems();
        $this->assertCount(1, $newsItems);
        $this->assertEquals('https://127.0.0.1/news/1', $newsItems[0]->getId());
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

        $feedIo = new FeedIo(new \FeedIo\Adapter\Guzzle\Client($guzzleClient), new NullLogger());
        $newsItemFetcher = new NewsItemFetcher($feedIo, '');

        $this->expectException(\RuntimeException::class);
        $newsItemFetcher->fetchNewsItems();
    }

    public function testExceptionOnInvalidResponse()
    {
        $guzzleMock = new MockHandler([
            new Response(200, [], 'foo')
        ]);
        $guzzleHhandler = HandlerStack::create($guzzleMock);
        $guzzleClient = new Client(['handler' => $guzzleHhandler]);

        $feedIo = new FeedIo(new \FeedIo\Adapter\Guzzle\Client($guzzleClient), new NullLogger());
        $newsItemFetcher = new NewsItemFetcher($feedIo, '');

        $this->expectException(\RuntimeException::class);
        $newsItemFetcher->fetchNewsItems();
    }
}
