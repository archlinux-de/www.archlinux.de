<?php

namespace App\Tests\Entity\Packages;

use App\ArchLinux\Package as DatabasePackage;
use App\Entity\Packages\Architecture;
use App\Entity\Packages\Package;
use App\Entity\Packages\Relations\AbstractRelation;
use App\Entity\Packages\Repository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\Packages\Package
 */
class PackageTest extends TestCase
{
    public function testJsonSerialize(): void
    {
        $repository = new Repository('core', Architecture::X86_64);
        $package = new Package($repository, 'pacman', '6.0-1', Architecture::X86_64);
        $package->setBuildDate(new \DateTime('2018-01-30'));
        $package->setDescription('foo bar');
        $package->setGroups(['base']);

        $json = (string)json_encode($package);
        $this->assertJson($json);
        $jsonArray = json_decode($json, true);
        $this->assertEquals(
            [
                'name' => 'pacman',
                'version' => '6.0-1',
                'architecture' => 'x86_64',
                'description' => 'foo bar',
                'builddate' => 'Tue, 30 Jan 2018 00:00:00 +0000',
                'repository' => [
                    'name' => 'core',
                    'architecture' => 'x86_64',
                    'testing' => false
                ],
                'groups' => [
                    'base'
                ]
            ],
            $jsonArray
        );
    }

    public function testCreateFromPackageDatabase(): void
    {
        $repository = new Repository('core', Architecture::X86_64);

        /** @var DatabasePackage|MockObject $databasePackage */
        $databasePackage = $this->createMock(DatabasePackage::class);
        $databasePackage->method('getName')->willReturn('pacman');
        $databasePackage->method('getVersion')->willReturn('1.0-1');
        $databasePackage->method('getArchitecture')->willReturn('x86_64');

        $package = Package::createFromPackageDatabase($repository, $databasePackage);

        $this->assertEquals('pacman', $package->getName());
        $this->assertEquals('1.0-1', $package->getVersion());
        $this->assertEquals('x86_64', $package->getArchitecture());
        $this->assertEquals('core', $package->getRepository()->getName());
    }

    /**
     * @param string $stringMethod
     * @dataProvider provideUpdateStringMethods
     */
    public function testUpdateFromPackageDatabase(string $stringMethod): void
    {
        $repository = new Repository('core', Architecture::X86_64);
        $package = new Package($repository, 'pacman', '1.0-1', 'x86_64');

        /** @var DatabasePackage|MockObject $databasePackage */
        $databasePackage = $this->createMock(DatabasePackage::class);
        $databasePackage->method($stringMethod)->willReturn('foo');

        $package->updateFromPackageDatabase($databasePackage);

        $this->assertEquals('foo', $package->$stringMethod());
    }

    /**
     * @return array
     */
    public function provideUpdateStringMethods(): array
    {
        return [
            ['getFileName'],
            ['getBase'],
            ['getName'],
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

        /** @var DatabasePackage|MockObject $databasePackage */
        $databasePackage = $this->createMock(DatabasePackage::class);
        $databasePackage->method('getPackager')->willReturn('foo<foo@localhost>');

        $package->updateFromPackageDatabase($databasePackage);

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

        /** @var DatabasePackage|MockObject $databasePackage */
        $databasePackage = $this->createMock(DatabasePackage::class);
        $databasePackage->method($timeMethod)->willReturn(new \DateTime('2018-01-30'));

        $package->updateFromPackageDatabase($databasePackage);
        $this->assertEquals(new \DateTime('2018-01-30'), $package->$timeMethod());
    }

    /**
     * @return array
     */
    public function provideTimeMethods(): array
    {
        return [
            ['getBuildDate'],
            ['getMTime']
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

        /** @var DatabasePackage|MockObject $databasePackage */
        $databasePackage = $this->createMock(DatabasePackage::class);
        $databasePackage->method($sizeMethod)->willReturn(1234);

        $package->updateFromPackageDatabase($databasePackage);

        $this->assertEquals(1234, $package->$sizeMethod());
    }

    /**
     * @return array
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

        /** @var DatabasePackage|MockObject $databasePackage */
        $databasePackage = $this->createMock(DatabasePackage::class);
        $databasePackage->method($listMethod)->willReturn($list);

        $package->updateFromPackageDatabase($databasePackage);

        $this->assertEquals($list, $package->$listMethod());
    }

    /**
     * @return array
     */
    public function provideListMethods(): array
    {
        return [
            ['getLicenses'],
            ['getGroups']
        ];
    }

    /**
     * @param string $databaseMethod
     * @param string $packageMethod
     * @dataProvider provideRelations
     */
    public function testUpdateRelation(string $databaseMethod, string $packageMethod): void
    {
        $repository = new Repository('core', Architecture::X86_64);
        $package = new Package($repository, 'pacman', '1.0-1', 'x86_64');

        /** @var DatabasePackage|MockObject $databasePackage */
        $databasePackage = $this->createMock(DatabasePackage::class);
        $databasePackage->method($databaseMethod)->willReturn(['foo', 'bar']);

        $package->updateFromPackageDatabase($databasePackage);

        /** @var AbstractRelation[] $relations */
        $relations = $package->$packageMethod();
        $this->assertCount(2, $relations);
        $this->assertEquals('foo', $relations[0]->getTargetName());
        $this->assertEquals('bar', $relations[1]->getTargetName());
    }

    /**
     * @return array
     */
    public function provideRelations(): array
    {
        return [
            ['getDepends', 'getDependencies'],
            ['getConflicts', 'getConflicts'],
            ['getReplaces', 'getReplacements'],
            ['getOptDepends', 'getOptionalDependencies'],
            ['getProvides', 'getProvisions'],
            ['getMakeDepends', 'getMakeDependencies'],
            ['getCheckDepends', 'getCheckDependencies']
        ];
    }

    public function testUpdateFiles(): void
    {
        $repository = new Repository('core', Architecture::X86_64);
        $package = new Package($repository, 'pacman', '1.0-1', 'x86_64');
        $pacmanFiles = ['usr/bin', 'usr/bin/pacman'];

        /** @var DatabasePackage|MockObject $databasePackage */
        $databasePackage = $this->createMock(DatabasePackage::class);
        $databasePackage->method('getFiles')->willReturn($pacmanFiles);

        $package->updateFromPackageDatabase($databasePackage);

        $this->assertEquals($pacmanFiles, iterator_to_array($package->getFiles()));
    }
}
