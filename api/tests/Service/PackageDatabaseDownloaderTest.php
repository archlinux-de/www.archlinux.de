<?php

namespace App\Tests\Service;

use App\Service\PackageDatabaseDownloader;
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

        $this->assertEquals('foo', $download);
    }

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
}
