<?php

namespace App\Tests\Service;

use App\Dto\AppStreamDataComponentDto;
use App\Entity\Packages\Repository;
use App\Repository\PackageRepository;
use App\Repository\RepositoryRepository;
use App\Service\AppStreamDataFetcher;
use App\Service\AppStreamDataHelper;
use App\Service\XmlExtractor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;

class AppStreamDataFetcherTest extends TestCase
{
    public function testFetchAppStreamData(): void
    {
        $content = $this->getMockContent();

        $coreRepoStub = $this->createStub(Repository::class);
        $coreRepoStub
            ->method('getName')
            ->willReturn('core');

        $extraRepoStub = $this->createStub(Repository::class);
        $extraRepoStub
            ->method('getName')
            ->willReturn('extra');

        $multilibRepoStub = $this->createStub(Repository::class);
        $multilibRepoStub
            ->method('getName')
            ->willReturn('multilib');

        $repositoryRepository = $this->createMock(RepositoryRepository::class);
        $repositoryRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['testing' => false])
            ->willReturn([$coreRepoStub, $extraRepoStub, $multilibRepoStub]);

        $packageRepository = $this->createStub(PackageRepository::class);

        $xmlExtractor = $this->createMock(XmlExtractor::class);
        $xmlExtractor
            ->expects($this->atLeastOnce())
            ->method('downloadAndExtract')
            ->willReturn($this->getMockContent());

        /** @var SerializerInterface&MockObject $serializer */
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->atLeastOnce())
            ->method('deserialize')
            ->with($content, AppStreamDataComponentDto::class . '[]', 'xml')
            ->willReturn([new AppStreamDataComponentDto('mousepad')]);

        $appStreamDataHelper = new AppStreamDataHelper(
            'https://sources.archlinux.org/other/packages/archlinux-appstream-data',
            'Components-x86_64.xml.gz',
            $packageRepository
        );


        $appStreamDataFetcher = new AppStreamDataFetcher(
            $serializer,
            $repositoryRepository,
            $xmlExtractor,
            $appStreamDataHelper,
        );

        $appStreamDataComponentDtos = [...$appStreamDataFetcher];

        var_dump($appStreamDataComponentDtos);

        $this->assertCount(3, $appStreamDataComponentDtos);
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
