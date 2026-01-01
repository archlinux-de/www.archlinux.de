<?php

namespace App\Tests\Service;

use App\Service\XmlExtractor;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class XmlExtractorTest extends TestCase
{
    public function testDownloadAndExtract(): void
    {
        $mockResponse = $this->createStub(ResponseInterface::class);
        $mockResponse
            ->method('getContent')
            ->willReturn(gzencode($this->getMockContent()));

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'https://foo.bar/')
            ->willReturn($mockResponse);

        $xmlExtractor = new XmlExtractor($httpClient);

        $xmlString = $xmlExtractor->downloadAndExtract('https://foo.bar/');

        $this->assertSame($this->getMockContent(), $xmlString);
    }

    private function getMockContent(): string
    {
        return '<?xml version="1.0" encoding="utf-8"?>
        <components version="1.0" origin="archlinux-arch-extra" time="20250202T100411">
        <component type="desktop-application">
          <id>com.abisource.AbiWord</id>
          <name>AbiWord</name>
          <summary>A word processor</summary>
          <project_license>GPL-2.0-or-later</project_license>
          <description>
            <p>
              AbiWord is a free word processing program. It is suitable for a
              wide variety of word processing tasks but remain focused on word
              processing.
            </p>
            <p>
              AbiWord is meant to remain relatively lightweight and support
              many file formats.
            </p>
          </description>
          <categories>
            <category>Office</category>
            <category>WordProcessor</category>
          </categories>
        </component>
        </components>';
    }
}
