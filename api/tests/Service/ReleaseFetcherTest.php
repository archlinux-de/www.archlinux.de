<?php

namespace App\Tests\Service;

use App\Entity\Release;
use App\Service\ReleaseFetcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Serializer\SerializerInterface;

class ReleaseFetcherTest extends TestCase
{
    public function testFetchReleases(): void
    {
        $content = 'foo';
        $responseMock = new MockResponse($content);
        $httpClient = new MockHttpClient($responseMock);

        /** @var SerializerInterface|MockObject $serializer */
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('deserialize')
            ->with($content, Release::class . '[]', 'json')
            ->willReturn([new Release('')]);

        $releaseFetcher = new ReleaseFetcher($httpClient, 'http://foo', $serializer);
        /** @var Release[] $releases */
        $releases = [...$releaseFetcher];

        $this->assertCount(1, $releases);
    }

    public function testExceptionOnEmptyResponse(): void
    {
        $httpClient = new MockHttpClient(new MockResponse());

        /** @var SerializerInterface|MockObject $serializer */
        $serializer = $this->createMock(SerializerInterface::class);

        $releaseFetcher = new ReleaseFetcher($httpClient, 'http://foo', $serializer);

        $this->expectException(\RuntimeException::class);
        [...$releaseFetcher];
    }

    public function testExceptionOnEmptyMirrorList(): void
    {
        $httpClient = new MockHttpClient(new MockResponse('foo'));

        /** @var SerializerInterface|MockObject $serializer */
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('deserialize')
            ->willReturn([]);

        $releaseFetcher = new ReleaseFetcher($httpClient, 'http://foo', $serializer);

        $this->expectException(\RuntimeException::class);
        [...$releaseFetcher];
    }
}
