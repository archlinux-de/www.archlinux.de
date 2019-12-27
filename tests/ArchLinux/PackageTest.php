<?php

namespace App\Tests\ArchLinux;

use App\ArchLinux\Package;
use App\ArchLinux\TemporaryDirectory;
use PHPUnit\Framework\TestCase;

class PackageTest extends TestCase
{
    /**
     * @param array<string|int> $desc
     * @param string[] $files
     * @return Package
     */
    private function createPackage(array $desc = [], array $files = []): Package
    {
        $packageDirectory = new TemporaryDirectory();
        file_put_contents(
            $packageDirectory->getPathname() . '/desc',
            implode("\n", $desc)
        );
        file_put_contents(
            $packageDirectory->getPathname() . '/files',
            implode("\n", array_merge(['%FILES%'], $files))
        );
        return new Package($packageDirectory);
    }

    public function testBaseFallsBackToName(): void
    {
        $name = 'foo';
        $package = $this->createPackage(['%NAME%', $name]);
        $this->assertEquals($name, $package->getBase());
    }

    public function testCompressedSize(): void
    {
        $compressedSize = 42;
        $package = $this->createPackage(['%CSIZE%', $compressedSize]);
        $this->assertEquals($compressedSize, $package->getCompressedSize());
    }

    public function testCompressedSizeDefaultsToZero(): void
    {
        $package = $this->createPackage();
        $this->assertEquals(0, $package->getCompressedSize());
    }

    public function testInstalledSize(): void
    {
        $installedSize = 42;
        $package = $this->createPackage(['%ISIZE%', $installedSize]);
        $this->assertEquals($installedSize, $package->getInstalledSize());
    }

    public function testInstalledSizeDefaultsToZero(): void
    {
        $package = $this->createPackage();
        $this->assertEquals(0, $package->getInstalledSize());
    }

    public function testSha256sumMightBeNull(): void
    {
        $package = $this->createPackage();
        $this->assertNull($package->getSha256sum());
    }

    public function testPGPSignatureMightBeNull(): void
    {
        $package = $this->createPackage();
        $this->assertNull($package->getPgpSignature());
    }

    public function testBuildDate(): void
    {
        $buildDate = (new \DateTime())->setTimestamp(1513515763);
        $package = $this->createPackage(['%BUILDDATE%', $buildDate->getTimestamp()]);
        $this->assertEquals($buildDate, $package->getBuildDate());
    }

    public function testBuildDateMightBeNull(): void
    {
        $package = $this->createPackage();
        $this->assertNull($package->getBuildDate());
    }

    public function testFilesAreOptional(): void
    {
        $package = $this->createPackage();
        $this->assertEquals([], $package->getFiles());
    }

    public function testFiles(): void
    {
        $files = ['/foo', '/bar'];
        $package = $this->createPackage([], $files);
        $this->assertEquals($files, $package->getFiles());
    }

    /**
     * @param string $key
     * @param string $getter
     * @dataProvider provideStringValues
     */
    public function testStringValue(string $key, string $getter): void
    {
        $value = 'foo';
        $package = $this->createPackage(['%' . $key . '%', $value]);
        $this->assertEquals($value, $package->$getter());
    }

    /**
     * @return array<array<string>>
     */
    public function provideStringValues(): array
    {
        return [
            ['FILENAME', 'getFileName'],
            ['BASE', 'getBase'],
            ['NAME', 'getName'],
            ['VERSION', 'getVersion'],
            ['DESC', 'getDescription'],
            ['MD5SUM', 'getMd5sum'],
            ['SHA256SUM', 'getSha256sum'],
            ['PGPSIG', 'getPgpSignature'],
            ['URL', 'getUrl'],
            ['ARCH', 'getArchitecture'],
            ['PACKAGER', 'getPackager']
        ];
    }

    /**
     * @param string $key
     * @param string $getter
     * @dataProvider provideLists
     */
    public function testList(string $key, string $getter): void
    {
        $list = ['foo', 'bar'];
        $package = $this->createPackage(array_merge(['%' . $key . '%'], $list));
        $this->assertEquals($list, $package->$getter());
    }

    /**
     * @param string $_
     * @param string $getter
     * @dataProvider provideLists
     */
    public function testUndefinedList(string $_, string $getter): void
    {
        $package = $this->createPackage();
        $this->assertNotEmpty($_);
        $this->assertEquals([], $package->$getter());
    }

    /**
     * @return array<array<string>>
     */
    public function provideLists(): array
    {
        return [
            ['GROUPS', 'getGroups'],
            ['LICENSE', 'getLicenses'],
            ['REPLACES', 'getReplaces'],
            ['DEPENDS', 'getDepends'],
            ['CONFLICTS', 'getConflicts'],
            ['PROVIDES', 'getProvides'],
            ['OPTDEPENDS', 'getOptDepends'],
            ['MAKEDEPENDS', 'getMakeDepends'],
            ['CHECKDEPENDS', 'getCheckDepends']
        ];
    }
}
