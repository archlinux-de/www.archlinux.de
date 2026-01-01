<?php

namespace App\Tests\Service;

use App\Entity\Packages\Package;
use App\Repository\PackageRepository;
use App\Service\AppStreamDataHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AppStreamDataHelperTest extends TestCase
{
    public function testObtainAppstreamDataVersion(): void
    {
        $package = $this->createStub(Package::class);
        $package
            ->method('getVersion')
            ->willReturn('20250202-2');

        $packageRepository = $this->createMock(PackageRepository::class);
        $packageRepository
            ->expects($this->once())
            ->method('getByName')
            ->with('extra', 'x86_64', 'archlinux-appstream-data')
            ->willReturn($package);

        $appStreamDataHelper = new AppStreamDataHelper(
            'https://foo.bar',
            'foobar.gz',
            $packageRepository
        );

        $result = $appStreamDataHelper->obtainAppstreamDataVersion();

        $this->assertEquals('20250202', $result);

    }
    public function testBuildUpstreamUrl(): void
    {
        /** @var PackageRepository&MockObject  $repositoryRepository */
        $packageRepository = $this->createStub(PackageRepository::class);
        $appStreamDataHelper = new AppStreamDataHelper(
            'https://foo.bar',
            'foobar.gz',
            $packageRepository
        );

        $result = $appStreamDataHelper->buildUpstreamUrl('42', 'super');
        $this->assertEquals('https://foo.bar/42/super/foobar.gz', $result);
    }

}
