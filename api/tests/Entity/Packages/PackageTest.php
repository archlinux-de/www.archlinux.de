<?php

namespace App\Tests\Entity\Packages;

use App\Entity\Packages\Architecture;
use App\Entity\Packages\Files;
use App\Entity\Packages\Package;
use App\Entity\Packages\Packager;
use App\Entity\Packages\Relations\CheckDependency;
use App\Entity\Packages\Relations\Conflict;
use App\Entity\Packages\Relations\Dependency;
use App\Entity\Packages\Relations\MakeDependency;
use App\Entity\Packages\Relations\OptionalDependency;
use App\Entity\Packages\Relations\Provision;
use App\Entity\Packages\Relations\Replacement;
use App\Entity\Packages\Repository;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(Package::class)]
class PackageTest extends TestCase
{
    #[DataProvider('provideUpdateStringMethods')]
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

    public static function provideUpdateStringMethods(): array
    {
        return [
            ['getBase'],
            ['getFileName'],
            ['getVersion'],
            ['getDescription'],
            ['getSha256sum'],
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

    #[DataProvider('provideTimeMethods')]
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

    public static function provideTimeMethods(): array
    {
        return [
            ['getBuildDate']
        ];
    }

    #[DataProvider('provideSiteMethods')]
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

    public static function provideSiteMethods(): array
    {
        return [
            ['getInstalledSize'],
            ['getCompressedSize']
        ];
    }

    #[DataProvider('provideListMethods')]
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

    public static function provideListMethods(): array
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

        $this->assertEquals($pacmanFiles, [...$package->getFiles()]);
    }

    public function testUpdateRelations(): void
    {
        $repository = new Repository('core', Architecture::X86_64);
        $package = new Package($repository, 'pacman', '1.0-1', 'x86_64');

        /** @var Package|MockObject $databasePackage */
        $databasePackage = $this->createMock(Package::class);
        $databasePackage->method('getDependencies')->willReturn(new ArrayCollection([new Dependency('a')]));
        $databasePackage->method('getConflicts')->willReturn(new ArrayCollection([new Conflict('b')]));
        $databasePackage->method('getReplacements')->willReturn(new ArrayCollection([new Replacement('c')]));
        $databasePackage->method('getOptionalDependencies')->willReturn(
            new ArrayCollection([new OptionalDependency('d')])
        );
        $databasePackage->method('getProvisions')->willReturn(new ArrayCollection([new Provision('e')]));
        $databasePackage->method('getMakeDependencies')->willReturn(new ArrayCollection([new MakeDependency('f')]));
        $databasePackage->method('getCheckDependencies')->willReturn(new ArrayCollection([new CheckDependency('g')]));

        $package->update($databasePackage);

        $this->assertInstanceOf(Dependency::class, $package->getDependencies()->first());
        $this->assertEquals('a', $package->getDependencies()->first()->getTargetName());

        $this->assertInstanceOf(Conflict::class, $package->getConflicts()->first());
        $this->assertEquals('b', $package->getConflicts()->first()->getTargetName());

        $this->assertInstanceOf(Replacement::class, $package->getReplacements()->first());
        $this->assertEquals('c', $package->getReplacements()->first()->getTargetName());

        $this->assertInstanceOf(OptionalDependency::class, $package->getOptionalDependencies()->first());
        $this->assertEquals('d', $package->getOptionalDependencies()->first()->getTargetName());

        $this->assertInstanceOf(Provision::class, $package->getProvisions()->first());
        $this->assertEquals('e', $package->getProvisions()->first()->getTargetName());

        $this->assertInstanceOf(MakeDependency::class, $package->getMakeDependencies()->first());
        $this->assertEquals('f', $package->getMakeDependencies()->first()->getTargetName());

        $this->assertInstanceOf(CheckDependency::class, $package->getCheckDependencies()->first());
        $this->assertEquals('g', $package->getCheckDependencies()->first()->getTargetName());
    }
}
