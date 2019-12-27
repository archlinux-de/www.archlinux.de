<?php

namespace App\Tests\Entity\Packages;

use App\Entity\Packages\Architecture;
use App\Entity\Packages\Package;
use App\Entity\Packages\Repository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RepositoryTest extends TestCase
{
    public function testSha256sum(): void
    {
        $sha256sum = hash('sha256', 'foo');
        $repository = new Repository('core', Architecture::X86_64);
        $this->assertSame($repository, $repository->setSha256sum($sha256sum));
        $this->assertSame($sha256sum, $repository->getSha256sum());
    }

    public function testAddPackage(): void
    {
        /** @var Package|MockObject $package */
        $package = $this->createMock(Package::class);
        $repository = new Repository('core', Architecture::X86_64);
        $this->assertSame($repository, $repository->addPackage($package));
        $packages = $repository->getPackages();
        $this->assertEquals(1, $packages->count());
        $this->assertSame($package, $packages->first());
    }
}
