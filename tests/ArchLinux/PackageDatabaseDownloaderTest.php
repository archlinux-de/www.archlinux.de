<?php

namespace App\Tests\ArchLinux;

use App\ArchLinux\PackageDatabaseDownloader;
use App\ArchLinux\PackageDatabaseMirror;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PackageDatabaseDownloaderTest extends TestCase
{
    /** @var ClientInterface|MockObject */
    private $guzzleClient;

    /** @var PackageDatabaseDownloader */
    private $downloader;

    public function setUp(): void
    {
        /** @var PackageDatabaseMirror|MockObject $packageDatabaseMirror */
        $packageDatabaseMirror = $this->createMock(PackageDatabaseMirror::class);
        $this->guzzleClient = $this->createMock(ClientInterface::class);
        $this->downloader = new PackageDatabaseDownloader($this->guzzleClient, $packageDatabaseMirror);
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

        $fileName = (string)$download->getRealPath();
        $this->assertFileExists($fileName);

        unset($download);
        gc_collect_cycles();
        $this->assertFileNotExists($fileName);
    }

    public function testCreateDatabase()
    {
        /** @var \SplFileObject|MockObject $packageDatabaseFile */
        $packageDatabaseFile = $this
            ->getMockBuilder(\SplFileObject::class)
            ->setConstructorArgs(['/dev/null'])
            ->getMock();
        $packageDatabaseFile
            ->method('getRealPath')
            ->willReturn('/dev/null');

        $database = $this->downloader->createDatabase($packageDatabaseFile);

        $this->assertCount(0, iterator_to_array($database));
    }
}
