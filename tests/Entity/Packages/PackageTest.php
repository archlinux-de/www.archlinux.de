<?php

namespace App\Tests\Entity\Packages;

use App\Entity\Packages\Architecture;
use App\Entity\Packages\Files;
use App\Entity\Packages\Package;
use App\Entity\Packages\Packager;
use App\Entity\Packages\Relations\AbstractRelation;
use App\Entity\Packages\Repository;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\Packages\Package
 */
class PackageTest extends TestCase
{
    /**
     * @param string $stringMethod
     * @dataProvider provideUpdateStringMethods
     */
    public function testUpdate(string $stringMethod): void
    {
        $repository = new Repository('core', Architecture::X86_64);
        $package = new Package($repository, 'pacman', '1.0-1', 'x86_64');

        /** @var Package|MockObject $databasePackage */
        $databasePackage = $this->createMock(Package::class);
        $databasePackage->method($stringMethod)->willReturn('foo');
        $databasePackage->method('getDependencies')->willReturn(new ArrayCollection());
        $databasePackage->method('getConflicts')->willReturn(new ArrayCollection());
        $databasePackage->method('getReplacements')->willReturn(new ArrayCollection());
        $databasePackage->method('getOptionalDependencies')->willReturn(new ArrayCollection());
        $databasePackage->method('getProvisions')->willReturn(new ArrayCollection());
        $databasePackage->method('getMakeDependencies')->willReturn(new ArrayCollection());
        $databasePackage->method('getCheckDependencies')->willReturn(new ArrayCollection());

        $package->update($databasePackage);

        $this->assertEquals('foo', $package->$stringMethod());
    }

    /**
     * @return array<array<string>>
     */
    public function provideUpdateStringMethods(): array
    {
        return [
            ['getFileName'],
            ['getVersion'],
            ['getDescription'],
            ['getMd5sum'],
            ['getSha256sum'],
            ['getPgpSignature'],
            ['getUrl'],
            ['getArchitecture']
        ];
    }

    public function testUpdatePackager(): void
    {
        $repository = new Repository('core', Architecture::X86_64);
        $package = new Package($repository, 'pacman', '1.0-1', 'x86_64');

        /** @var Package|MockObject $databasePackage */
        $databasePackage = $this->createMock(Package::class);
        $databasePackage->method('getDependencies')->willReturn(new ArrayCollection());
        $databasePackage->method('getConflicts')->willReturn(new ArrayCollection());
        $databasePackage->method('getReplacements')->willReturn(new ArrayCollection());
        $databasePackage->method('getOptionalDependencies')->willReturn(new ArrayCollection());
        $databasePackage->method('getProvisions')->willReturn(new ArrayCollection());
        $databasePackage->method('getMakeDependencies')->willReturn(new ArrayCollection());
        $databasePackage->method('getCheckDependencies')->willReturn(new ArrayCollection());
        $databasePackage->method('getPackager')->willReturn(new Packager('foo', 'foo@localhost'));

        $package->update($databasePackage);

        $this->assertNotNull($package->getPackager());
        $this->assertEquals('foo', $package->getPackager()->getName());
        $this->assertEquals('foo@localhost', $package->getPackager()->getEmail());
    }

    /**
     * @param string $timeMethod
     * @dataProvider provideTimeMethods
     */
    public function testUpdateTime(string $timeMethod): void
    {
        $repository = new Repository('core', Architecture::X86_64);
        $package = new Package($repository, 'pacman', '1.0-1', 'x86_64');

        /** @var Package|MockObject $databasePackage */
        $databasePackage = $this->createMock(Package::class);
        $databasePackage->method($timeMethod)->willReturn(new \DateTime('2018-01-30'));
        $databasePackage->method('getDependencies')->willReturn(new ArrayCollection());
        $databasePackage->method('getConflicts')->willReturn(new ArrayCollection());
        $databasePackage->method('getReplacements')->willReturn(new ArrayCollection());
        $databasePackage->method('getOptionalDependencies')->willReturn(new ArrayCollection());
        $databasePackage->method('getProvisions')->willReturn(new ArrayCollection());
        $databasePackage->method('getMakeDependencies')->willReturn(new ArrayCollection());
        $databasePackage->method('getCheckDependencies')->willReturn(new ArrayCollection());

        $package->update($databasePackage);
        $this->assertEquals(new \DateTime('2018-01-30'), $package->$timeMethod());
    }

    /**
     * @return array<array<string>>
     */
    public function provideTimeMethods(): array
    {
        return [
            ['getBuildDate']
        ];
    }

    /**
     * @param string $sizeMethod
     * @dataProvider provideSiteMethods
     */
    public function testUpdateSize(string $sizeMethod): void
    {
        $repository = new Repository('core', Architecture::X86_64);
        $package = new Package($repository, 'pacman', '1.0-1', 'x86_64');

        /** @var Package|MockObject $databasePackage */
        $databasePackage = $this->createMock(Package::class);
        $databasePackage->method($sizeMethod)->willReturn(1234);
        $databasePackage->method('getDependencies')->willReturn(new ArrayCollection());
        $databasePackage->method('getConflicts')->willReturn(new ArrayCollection());
        $databasePackage->method('getReplacements')->willReturn(new ArrayCollection());
        $databasePackage->method('getOptionalDependencies')->willReturn(new ArrayCollection());
        $databasePackage->method('getProvisions')->willReturn(new ArrayCollection());
        $databasePackage->method('getMakeDependencies')->willReturn(new ArrayCollection());
        $databasePackage->method('getCheckDependencies')->willReturn(new ArrayCollection());

        $package->update($databasePackage);

        $this->assertEquals(1234, $package->$sizeMethod());
    }

    /**
     * @return array<array<string>>
     */
    public function provideSiteMethods(): array
    {
        return [
            ['getInstalledSize'],
            ['getCompressedSize']
        ];
    }

    /**
     * @param string $listMethod
     * @dataProvider provideListMethods
     */
    public function testUpdateLists(string $listMethod): void
    {
        $repository = new Repository('core', Architecture::X86_64);
        $package = new Package($repository, 'pacman', '1.0-1', 'x86_64');
        $list = ['foo', 'bar'];

        /** @var Package|MockObject $databasePackage */
        $databasePackage = $this->createMock(Package::class);
        $databasePackage->method($listMethod)->willReturn($list);
        $databasePackage->method('getDependencies')->willReturn(new ArrayCollection());
        $databasePackage->method('getConflicts')->willReturn(new ArrayCollection());
        $databasePackage->method('getReplacements')->willReturn(new ArrayCollection());
        $databasePackage->method('getOptionalDependencies')->willReturn(new ArrayCollection());
        $databasePackage->method('getProvisions')->willReturn(new ArrayCollection());
        $databasePackage->method('getMakeDependencies')->willReturn(new ArrayCollection());
        $databasePackage->method('getCheckDependencies')->willReturn(new ArrayCollection());

        $package->update($databasePackage);

        $this->assertEquals($list, $package->$listMethod());
    }

    /**
     * @return array<array<string>>
     */
    public function provideListMethods(): array
    {
        return [
            ['getLicenses'],
            ['getGroups']
        ];
    }

    public function testUpdateFiles(): void
    {
        $repository = new Repository('core', Architecture::X86_64);
        $package = new Package($repository, 'pacman', '1.0-1', 'x86_64');
        $pacmanFiles = ['usr/bin', 'usr/bin/pacman'];

        /** @var Package|MockObject $databasePackage */
        $databasePackage = $this->createMock(Package::class);
        $databasePackage->method('getFiles')->willReturn(Files::createFromArray($pacmanFiles));
        $databasePackage->method('getDependencies')->willReturn(new ArrayCollection());
        $databasePackage->method('getConflicts')->willReturn(new ArrayCollection());
        $databasePackage->method('getReplacements')->willReturn(new ArrayCollection());
        $databasePackage->method('getOptionalDependencies')->willReturn(new ArrayCollection());
        $databasePackage->method('getProvisions')->willReturn(new ArrayCollection());
        $databasePackage->method('getMakeDependencies')->willReturn(new ArrayCollection());
        $databasePackage->method('getCheckDependencies')->willReturn(new ArrayCollection());

        $package->update($databasePackage);

        $this->assertEquals($pacmanFiles, iterator_to_array($package->getFiles()));
    }
}
