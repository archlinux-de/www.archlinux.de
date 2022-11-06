<?php

namespace App\Tests\Serializer;

use App\Entity\Packages\Package;
use App\Entity\Packages\Packager;
use App\Entity\Packages\Relations\Dependency;
use App\Entity\Packages\Relations\Provision;
use App\Entity\Packages\Repository;
use App\Serializer\PackageDenormalizer;
use PHPUnit\Framework\TestCase;

class PackageDenormalizerTest extends TestCase
{
    public function testSupportsDenormalization(): void
    {
        $this->assertTrue(
            (new PackageDenormalizer())->supportsDenormalization(
                [],
                Package::class,
                'pacman-database',
                ['repository' => new Repository('core', 'x86_64')]
            )
        );
    }

    public function testDenormalize(): void
    {
        $repository = new Repository('core', 'x86_64');
        $packageDenormalizer = new PackageDenormalizer();

        $package = $packageDenormalizer->denormalize(
            [
                'NAME' => 'pacman',
                'VERSION' => '5.2.1-4',
                'ARCH' => 'x86_64',
                'FILENAME' => 'pacman-5.2.1-4-x86_64.pkg.tar.zst',
                'URL' => 'https://www.archlinux.org/pacman/',
                'DESC' => 'A library-based package manager with dependency support',
                'BASE' => 'pacman',
                'BUILDDATE' => 1578623077,
                'CSIZE' => 856711,
                'ISIZE' => 4623024,
                'PACKAGER' => 'foo<foo@localhost>',
                'SHA256SUM' => 'a3f6168d59005527b98139607db510fad42a685662f6e86975d941c8c3c476ab',
                'LICENSE' => 'GPL',
                'GROUPS' => 'base-devel',
                'FILES' => '',
                'DEPENDS' => ['bash', 'glibc>=1.0'],
                'CONFLICTS' => '',
                'REPLACES' => '',
                'OPTDEPENDS' => '',
                'PROVIDES' => 'libalpm.so=12-64',
                'MAKEDEPENDS' => '',
                'CHECKDEPENDS' => '',
            ],
            Package::class,
            'pacman-database',
            ['repository' => $repository]
        );

        $this->assertEquals('pacman', $package->getName());

        $packager = $package->getPackager();
        $this->assertInstanceOf(Packager::class, $packager);
        $this->assertEquals('foo', $packager->getName());
        $this->assertEquals('foo@localhost', $packager->getEmail());

        $dependencies = $package->getDependencies();
        $this->assertCount(2, $dependencies);

        $dependency1 = $dependencies->first();
        $this->assertInstanceOf(Dependency::class, $dependency1);
        $this->assertEquals('bash', $dependency1->getTargetName());
        $this->assertNull($dependency1->getTargetVersion());

        $dependency2 = $dependencies->last();
        $this->assertInstanceOf(Dependency::class, $dependency2);
        $this->assertEquals('glibc', $dependency2->getTargetName());
        $this->assertEquals('>=1.0', $dependency2->getTargetVersion());

        $this->assertCount(1, $package->getProvisions());
        $this->assertInstanceOf(Provision::class, $package->getProvisions()->first());
        $this->assertEquals('libalpm.so', $package->getProvisions()->first()->getTargetName());
        $this->assertEquals('=12-64', $package->getProvisions()->first()->getTargetVersion());
    }

    public function testEmptyUrl(): void
    {
        $repository = new Repository('core', 'x86_64');
        $packageDenormalizer = new PackageDenormalizer();

        $package = $packageDenormalizer->denormalize(
            [
                'NAME' => 'pacman',
                'VERSION' => '5.2.1-4',
                'ARCH' => 'x86_64',
                'FILENAME' => 'pacman-5.2.1-4-x86_64.pkg.tar.zst',
                'URL' => '',
                'DESC' => 'A library-based package manager with dependency support',
                'BUILDDATE' => 1578623077,
                'CSIZE' => 856711,
                'ISIZE' => 4623024,
                'PACKAGER' => 'foo<foo@localhost>',
                'SHA256SUM' => 'a3f6168d59005527b98139607db510fad42a685662f6e86975d941c8c3c476ab',
                'FILES' => '',
            ],
            Package::class,
            'pacman-database',
            ['repository' => $repository]
        );

        $this->assertNull($package->getUrl());
    }
}
