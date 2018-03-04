<?php

namespace App\Tests\ArchLinux;

use App\ArchLinux\PackageDatabaseDownloader;
use App\ArchLinux\PackageDatabaseMirror;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class PackageDatabaseDownloaderTest extends TestCase
{
    /** @var ClientInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $guzzleClient;

    /** @var PackageDatabaseDownloader */
    private $downloader;

    public function setUp()
    {
        $this->guzzleClient = $this->createMock(ClientInterface::class);
        $packageDatabaseMirror = $this->createMock(PackageDatabaseMirror::class);
        $this->downloader = new PackageDatabaseDownloader($this->guzzleClient, $packageDatabaseMirror);
    }

    public function testDownloadReturnsFile()
    {
        $this->guzzleClient->method('request')->willReturn(new Response());
        $download = $this->downloader->download('', '');
        $this->assertInstanceOf(\SplFileObject::class, $download);
    }

    public function testFileModificationTimeIsInSyncWithServerResponse()
    {
        $timestamp = 42;
        $this->guzzleClient
            ->method('request')
            ->willReturn(new Response(
                200,
                ['Last-Modified' => date(\DateTime::RFC1123, $timestamp)]
            ));

        $download = $this->downloader->download('', '');
        $this->assertEquals($timestamp, $download->getMTime());
    }

    public function testTemporaryFileIsRemovedByGarbageCollector()
    {
        $this->guzzleClient->method('request')->willReturn(new Response());
        $download = $this->downloader->download('', '');

        $fileName = $download->getRealPath();
        $this->assertFileExists($fileName);

        unset($download);
        gc_collect_cycles();
        $this->assertFileNotExists($fileName);
    }
}
