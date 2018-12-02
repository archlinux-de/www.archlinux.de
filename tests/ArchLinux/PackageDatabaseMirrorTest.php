<?php

namespace App\Tests\ArchLinux;

use App\ArchLinux\PackageDatabaseMirror;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class PackageDatabaseMirrorTest extends TestCase
{
    public function testGetMirrorUrl()
    {
        /** @var ClientInterface|MockObject $guzzleClient */
        $guzzleClient = $this->createMock(ClientInterface::class);

        /** @var CacheItemPoolInterface|MockObject $cache */
        $cache = $this->createMock(CacheItemPoolInterface::class);

        $packageDatabaseMirror = new PackageDatabaseMirror($guzzleClient, $cache, 'foo');

        $this->assertEquals('foo', $packageDatabaseMirror->getMirrorUrl());
    }

    public function testHasUpdatedIsTrueForNewMirror()
    {
        /** @var ClientInterface|MockObject $guzzleClient */
        $guzzleClient = $this->createMock(ClientInterface::class);

        $cache = new ArrayAdapter();

        $packageDatabaseMirror = new PackageDatabaseMirror($guzzleClient, $cache, 'foo');
        $this->assertTrue($packageDatabaseMirror->hasUpdated());
    }

    /**
     * @param int $oldLastUpdated
     * @param int $newLastUpdated
     * @dataProvider provideLastUpdated
     */
    public function testHasUpdated(int $oldLastUpdated, int $newLastUpdated)
    {
        $guzzleMock = new MockHandler([new Response(200, [], $newLastUpdated)]);
        $guzzleHhandler = HandlerStack::create($guzzleMock);
        $guzzleClient = new Client(['handler' => $guzzleHhandler]);

        /** @var CacheItemInterface|MockObject $cacheItem */
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem
            ->expects($this->once())
            ->method('isHit')
            ->willReturn(true);
        $cacheItem
            ->expects($this->once())
            ->method('get')
            ->willReturn($oldLastUpdated);

        /** @var CacheItemPoolInterface|MockObject $cache */
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache
            ->expects($this->once())
            ->method('getItem')
            ->with('UpdatePackages-lastupdate')
            ->willReturn($cacheItem);

        $packageDatabaseMirror = new PackageDatabaseMirror($guzzleClient, $cache, 'foo');
        $this->assertEquals($oldLastUpdated != $newLastUpdated, $packageDatabaseMirror->hasUpdated());
    }

    public function testUpdateLastUpdate()
    {
        /** @var ClientInterface|MockObject $guzzleClient */
        $guzzleClient = $this->createMock(ClientInterface::class);

        $cache = new ArrayAdapter();

        $packageDatabaseMirror = new PackageDatabaseMirror($guzzleClient, $cache, '');
        $packageDatabaseMirror->updateLastUpdate();

        $this->assertEquals(0, $cache->getItem('UpdatePackages-lastupdate')->get());
    }

    /**
     * @return array
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
