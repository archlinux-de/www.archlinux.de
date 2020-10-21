<?php

namespace App\Tests\Service;

use App\Entity\NewsItem;
use App\Service\NewsItemFetcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Serializer\SerializerInterface;

class NewsItemFetcherTest extends TestCase
{
    public function testFetchNewsItems(): void
    {
        $content = 'foo';
        $responseMock = new MockResponse($content);
        $httpClient = new MockHttpClient($responseMock);

        /** @var SerializerInterface|MockObject $serializer */
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('deserialize')
            ->with($content, NewsItem::class . '[]', 'xml')
            ->willReturn([new NewsItem(1)]);

        $newsItemFetcher = new NewsItemFetcher('http://foo', $httpClient, $serializer);

        /** @var NewsItem[] $newsItems */
        $newsItems = [...$newsItemFetcher];
        $this->assertCount(1, $newsItems);
    }

    public function testExceptionOnEmptyResponse(): void
    {
        $httpClient = new MockHttpClient(new MockResponse(''));

        /** @var SerializerInterface|MockObject $serializer */
        $serializer = $this->createMock(SerializerInterface::class);

        $newsItemFetcher = new NewsItemFetcher('http://foo', $httpClient, $serializer);

        $this->expectException(\RuntimeException::class);
        [...$newsItemFetcher];
    }

    public function testExceptionOnInvalidResponse(): void
    {
        $httpClient = new MockHttpClient(new MockResponse('foo'));

        /** @var SerializerInterface|MockObject $serializer */
        $serializer = $this->createMock(SerializerInterface::class);

        $newsItemFetcher = new NewsItemFetcher('http://foo', $httpClient, $serializer);

        $this->expectException(\Exception::class);
        [...$newsItemFetcher];
    }

    public function testExceptionOnIncompleteResponse(): void
    {
        $httpClient = new MockHttpClient(new MockResponse('foo'));

        /** @var SerializerInterface|MockObject $serializer */
        $serializer = $this->createMock(SerializerInterface::class);

        $newsItemFetcher = new NewsItemFetcher('http://foo', $httpClient, $serializer);

        $this->expectException(\Throwable::class);
        [...$newsItemFetcher];
    }
}
