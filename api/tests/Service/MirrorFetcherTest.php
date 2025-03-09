<?php

namespace App\Tests\Service;

use App\Entity\Mirror;
use App\Service\MirrorFetcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Serializer\SerializerInterface;

class MirrorFetcherTest extends TestCase
{
    public function testFetchMirrors(): void
    {
        $content = 'foo';
        $responseMock = new MockResponse($content);
        $httpClient = new MockHttpClient($responseMock);

        /** @var SerializerInterface&MockObject $serializer */
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('deserialize')
            ->with($content, Mirror::class . '[]', 'json')
            ->willReturn([new Mirror('')]);

        $mirrorFetcher = new MirrorFetcher($httpClient, 'http://foo', $serializer);
        /** @var Mirror[] $mirrors */
        $mirrors = [...$mirrorFetcher];

        $this->assertCount(1, $mirrors);
    }

    public function testExceptionOnEmptyResponse(): void
    {
        $httpClient = new MockHttpClient(new MockResponse());

        /** @var SerializerInterface&MockObject $serializer */
        $serializer = $this->createMock(SerializerInterface::class);

        $mirrorFetcher = new MirrorFetcher($httpClient, 'http://foo', $serializer);

        $this->expectException(\RuntimeException::class);
        $this->assertIsArray([...$mirrorFetcher]);
    }

    public function testExceptionOnEmptyMirrorList(): void
    {
        $httpClient = new MockHttpClient(new MockResponse('foo'));

        /** @var SerializerInterface&MockObject $serializer */
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('deserialize')
            ->willReturn([]);

        $mirrorFetcher = new MirrorFetcher($httpClient, 'http://foo', $serializer);

        $this->expectException(\RuntimeException::class);
        $this->assertIsArray([...$mirrorFetcher]);
    }
}
