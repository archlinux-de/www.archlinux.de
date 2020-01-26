<?php

namespace App\Tests\Service;

use App\Service\PackageDatabaseMirror;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PackageDatabaseMirrorTest extends TestCase
{
    public function testGetMirrorUrl(): void
    {
        /** @var HttpClientInterface|MockObject $httpClient */
        $httpClient = $this->createMock(HttpClientInterface::class);

        /** @var CacheItemPoolInterface|MockObject $cache */
        $cache = $this->createMock(CacheItemPoolInterface::class);

        $packageDatabaseMirror = new PackageDatabaseMirror($httpClient, $cache, 'foo');

        $this->assertEquals('foo', $packageDatabaseMirror->getMirrorUrl());
    }

    public function testHasUpdatedIsTrueForNewMirror(): void
    {
        /** @var HttpClientInterface|MockObject $httpClient */
        $httpClient = $this->createMock(HttpClientInterface::class);

        $cache = new ArrayAdapter();

        $packageDatabaseMirror = new PackageDatabaseMirror($httpClient, $cache, 'foo');
        $this->assertTrue($packageDatabaseMirror->hasUpdated());
    }

    /**
     * @param int $oldLastUpdated
     * @param int $newLastUpdated
     * @dataProvider provideLastUpdated
     */
    public function testHasUpdated(int $oldLastUpdated, int $newLastUpdated): void
    {
        $httpClient = new MockHttpClient(new MockResponse((string)$newLastUpdated));

        /** @var CacheItemInterface|MockObject $cacheItem */
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem
            ->expects($this->once())
            ->method('isHit')
            ->willReturn(true);
        $cacheItem
            ->expects($this->once())
            ->method('get')
            ->willReturn(hash('sha256', (string)$oldLastUpdated));

        /** @var CacheItemPoolInterface|MockObject $cache */
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache
            ->expects($this->once())
            ->method('getItem')
            ->with(PackageDatabaseMirror::CACHE_KEY)
            ->willReturn($cacheItem);

        $packageDatabaseMirror = new PackageDatabaseMirror($httpClient, $cache, 'http://foo');
        $this->assertEquals($oldLastUpdated != $newLastUpdated, $packageDatabaseMirror->hasUpdated());
    }

    public function testUpdateLastUpdate(): void
    {
        /** @var HttpClientInterface|MockObject $httpClient */
        $httpClient = $this->createMock(HttpClientInterface::class);

        $cache = new ArrayAdapter();

        $packageDatabaseMirror = new PackageDatabaseMirror($httpClient, $cache, '');
        $packageDatabaseMirror->updateLastUpdate();

        $this->assertEquals('', $cache->getItem(PackageDatabaseMirror::CACHE_KEY)->get());
    }

    /**
     * @return array<array<int>>
     */
    public function provideLastUpdated(): array
    {
        return [
            [0, 1],
            [1, 0],
            [0, 0]
        ];
    }
}
