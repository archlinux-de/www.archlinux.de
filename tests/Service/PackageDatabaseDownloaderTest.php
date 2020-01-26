<?php

namespace App\Tests\Service;

use App\Service\PackageDatabaseMirror;
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
     * @return \App\Service\PackageDatabaseDownloader
     */
    public function createDownloader(ResponseInterface $response): \App\Service\PackageDatabaseDownloader
    {
        /** @var PackageDatabaseMirror|MockObject $packageDatabaseMirror */
        $packageDatabaseMirror = $this->createMock(\App\Service\PackageDatabaseMirror::class);
        $packageDatabaseMirror
            ->expects($this->any())
            ->method('getMirrorUrl')
            ->willReturn('http://foo');

        return new \App\Service\PackageDatabaseDownloader(new MockHttpClient($response), $packageDatabaseMirror);
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
}
