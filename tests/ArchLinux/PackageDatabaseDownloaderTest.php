<?php

namespace App\Tests\ArchLinux;

use App\ArchLinux\PackageDatabaseDownloader;
use App\ArchLinux\PackageDatabaseMirror;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

class PackageDatabaseDownloaderTest extends TestCase
{
    public function testFileIsWritten(): void
    {
        $responseMock = new MockResponse('foo');
        $download = $this->createDownloader($responseMock)->download('', '');

        $fileName = (string)$download->getRealPath();
        $this->assertFileExists($fileName);
        $this->assertStringEqualsFile($fileName, 'foo');
    }

    /**
     * @param ResponseInterface $response
     * @return PackageDatabaseDownloader
     */
    public function createDownloader(ResponseInterface $response): PackageDatabaseDownloader
    {
        /** @var PackageDatabaseMirror|MockObject $packageDatabaseMirror */
        $packageDatabaseMirror = $this->createMock(PackageDatabaseMirror::class);
        $packageDatabaseMirror
            ->expects($this->any())
            ->method('getMirrorUrl')
            ->willReturn('http://foo');

        return new PackageDatabaseDownloader(new MockHttpClient($response), $packageDatabaseMirror);
    }

    public function testTemporaryFileIsRemovedByGarbageCollector(): void
    {
        $responseMock = new MockResponse('');
        $download = $this->createDownloader($responseMock)->download('', '');

        $fileName = (string)$download->getRealPath();
        $this->assertFileExists($fileName);

        unset($download);
        gc_collect_cycles();
        $this->assertFileNotExists($fileName);
    }

    public function testCreateDatabase(): void
    {
        /** @var \SplFileObject|MockObject $packageDatabaseFile */
        $packageDatabaseFile = $this
            ->getMockBuilder(\SplFileObject::class)
            ->setConstructorArgs(['/dev/null'])
            ->getMock();
        $packageDatabaseFile
            ->method('getRealPath')
            ->willReturn('/dev/null');

        $database = $this->createDownloader(new MockResponse())->createDatabase($packageDatabaseFile);

        $this->assertCount(0, iterator_to_array($database));
    }
}
