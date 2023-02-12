<?php

namespace App\Tests\Serializer;

use App\Entity\Packages\Package;
use App\Entity\Packages\Packager;
use App\Entity\Packages\Relations\AbstractRelation;
use App\Entity\Packages\Relations\Dependency;
use App\Entity\Packages\Relations\Provision;
use App\Entity\Packages\Repository;
use App\Serializer\PackageDenormalizer;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
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

    private function assertDependency(array $expected, Collection $dependencies): void
    {
        /** @var AbstractRelation[] $dependencyArray */
        $dependencyArray = $dependencies->toArray();
        $this->assertEquals(
            $expected,
            array_map(
                fn (AbstractRelation $relation): array => [$relation->getTargetName(), $relation->getTargetVersion()],
                $dependencyArray
            )
        );
    }

    #[DataProvider('provideDependencies')]
    public function testDependencies(string $type, array|string|null $values, array $expected): void
    {
        $repository = new Repository('core', 'x86_64');
        $packageDenormalizer = new PackageDenormalizer();

        $data = [
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
            'FILES' => ''
        ];
        $data[$type] = $values;
        $package = $packageDenormalizer->denormalize(
            $data,
            Package::class,
            'pacman-database',
            ['repository' => $repository]
        );

        switch ($type) {
            case 'DEPENDS':
                $this->assertDependency($expected, $package->getDependencies());
                break;
            case 'CONFLICTS':
                $this->assertDependency($expected, $package->getConflicts());
                break;
            case 'REPLACES':
                $this->assertDependency($expected, $package->getReplacements());
                break;
            case 'OPTDEPENDS':
                $this->assertDependency($expected, $package->getOptionalDependencies());
                break;
            case 'PROVIDES':
                $this->assertDependency($expected, $package->getProvisions());
                break;
            case 'MAKEDEPENDS':
                $this->assertDependency($expected, $package->getMakeDependencies());
                break;
            case 'CHECKDEPENDS':
                $this->assertDependency($expected, $package->getCheckDependencies());
                break;
            default:
                $this->fail(sprintf('Invalid dependency type: %s', $type));
        }
    }

    public static function provideDependencies(): iterable
    {
        $types = ['DEPENDS', 'CONFLICTS', 'REPLACES', 'OPTDEPENDS', 'PROVIDES', 'MAKEDEPENDS', 'CHECKDEPENDS'];
        foreach ($types as $type) {
            yield [$type, null, []];
            yield [$type, 'foo', [['foo', null]]];
            yield [$type, ['foo', 'bar'], [['foo', null], ['bar', null]]];
            yield [$type, 'foo=1.23', [['foo', '=1.23']]];
            yield [$type, 'foo.so=3-64', [['foo.so', '=3-64']]];
            yield [$type, 'foo: bar baz', [['foo', null]]];
            yield [$type, 'foo>4.5: bar baz', [['foo', '>4.5']]];
            yield [$type, 'foo>1:4.5: bar baz', [['foo', '>1:4.5']]];
        }
    }

    #[DataProvider('providePackagers')]
    public function testPackager(?string $packagerString, ?string $expectedName, ?string $expectedEmail): void
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
                'PACKAGER' => $packagerString,
                'SHA256SUM' => 'a3f6168d59005527b98139607db510fad42a685662f6e86975d941c8c3c476ab',
                'FILES' => '',
            ],
            Package::class,
            'pacman-database',
            ['repository' => $repository]
        );

        if (!$expectedName && !$expectedEmail) {
            $this->assertNull($package->getPackager());
        } else {
            $this->assertInstanceOf(Packager::class, $package->getPackager());
            $this->assertEquals($expectedName, $package->getPackager()->getName());
            $this->assertEquals($expectedEmail, $package->getPackager()->getEmail());
        }
    }

    public static function providePackagers(): iterable
    {
        yield [null, null, null];
        yield ['', null, null];
        yield ['foo<bar@archlinux.org>', 'foo', 'bar@archlinux.org'];
        yield ['  foo bar   <bar@archlinux.org>  ', 'foo bar', 'bar@archlinux.org'];
    }
}
