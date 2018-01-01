<?php

namespace App\Tests\ArchLinux;

use App\ArchLinux\Package;
use App\ArchLinux\TemporaryDirectory;
use PHPUnit\Framework\TestCase;

class PackageTest extends TestCase
{
    public function testModificationTimeIsValid()
    {
        $package = $this->createPackage();
        $this->assertGreaterThan(time() - 42, $package->getMTime()->getTimestamp());
    }

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

    public function testBaseFallsBackToName()
    {
        $name = 'foo';
        $package = $this->createPackage(['%NAME%', $name]);
        $this->assertEquals($name, $package->getBase());
    }

    public function testCompressedSize()
    {
        $compressedSize = 42;
        $package = $this->createPackage(['%CSIZE%', $compressedSize]);
        $this->assertEquals($compressedSize, $package->getCompressedSize());
    }

    public function testCompressedSizeDefaultsToZero()
    {
        $package = $this->createPackage();
        $this->assertEquals(0, $package->getCompressedSize());
    }

    public function testInstalledSize()
    {
        $installedSize = 42;
        $package = $this->createPackage(['%ISIZE%', $installedSize]);
        $this->assertEquals($installedSize, $package->getInstalledSize());
    }

    public function testInstalledSizeDefaultsToZero()
    {
        $package = $this->createPackage();
        $this->assertEquals(0, $package->getInstalledSize());
    }

    public function testSha256sumMightBeNull()
    {
        $package = $this->createPackage();
        $this->assertNull($package->getSHA256SUM());
    }

    public function testPGPSignatureMightBeNull()
    {
        $package = $this->createPackage();
        $this->assertNull($package->getPGPSignature());
    }

    public function testBuildDate()
    {
        $buildDate = (new \DateTime())->setTimestamp(1513515763);
        $package = $this->createPackage(['%BUILDDATE%', $buildDate->getTimestamp()]);
        $this->assertEquals($buildDate, $package->getBuildDate());
    }

    public function testBuildDateMightBeNull()
    {
        $package = $this->createPackage();
        $this->assertNull($package->getBuildDate());
    }

    public function testFilesAreOptional()
    {
        $package = $this->createPackage();
        $this->assertEquals([], $package->getFiles());
    }

    public function testFiles()
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
    public function testStringValue(string $key, string $getter)
    {
        $value = 'foo';
        $package = $this->createPackage(['%' . $key . '%', $value]);
        $this->assertEquals($value, call_user_func([$package, $getter]));
    }

    /**
     * @return array
     */
    public function provideStringValues(): array
    {
        return [
            ['FILENAME', 'getFileName'],
            ['BASE', 'getBase'],
            ['NAME', 'getName'],
            ['VERSION', 'getVersion'],
            ['DESC', 'getDescription'],
            ['MD5SUM', 'getMD5SUM'],
            ['SHA256SUM', 'getSHA256SUM'],
            ['PGPSIG', 'getPGPSignature'],
            ['URL', 'getURL'],
            ['ARCH', 'getArch'],
            ['PACKAGER', 'getPackager']
        ];
    }

    /**
     * @param string $key
     * @param string $getter
     * @dataProvider provideLists
     */
    public function testList(string $key, string $getter)
    {
        $list = ['foo', 'bar'];
        $package = $this->createPackage(array_merge(['%' . $key . '%'], $list));
        $this->assertEquals($list, call_user_func([$package, $getter]));
    }

    /**
     * @param string $_
     * @param string $getter
     * @dataProvider provideLists
     */
    public function testUndefinedList(string $_, string $getter)
    {
        $package = $this->createPackage();
        $this->assertEquals([], call_user_func([$package, $getter]));
    }

    /**
     * @return array
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
