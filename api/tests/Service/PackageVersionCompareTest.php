<?php

namespace App\Tests\Service;

use App\Entity\Packages\Version;
use App\Service\Libalpm;
use App\Service\PackageVersionCompare;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PackageVersionCompareTest extends TestCase
{
    private PackageVersionCompare $packageVersionCompare;

    public function setUp(): void
    {
        $this->packageVersionCompare = new PackageVersionCompare(new Libalpm());
    }

    #[DataProvider('provideVersions')]
    public function testCompare(string $version1, string $version2, int $expected): void
    {
        $this->assertEquals(
            $expected,
            $this->packageVersionCompare->compare(
                Version::createFromString($version1),
                Version::createFromString($version2)
            )
        );
    }

    public static function provideVersions(): iterable
    {
        // test cases from https://gitlab.archlinux.org/pacman/pacman/-/blob/master/test/util/vercmptest.sh

        // all similar length, no pkgrel
        yield ['1.5.0', '1.5.0', 0];
        yield ['1.5.1', '1.5.0', 1];

        // mixed length
        yield ['1.5.1', '1.5', 1];

        // with pkgrel, simple
        yield ['1.5.0-1', '1.5.0-1', 0];
        yield ['1.5.0-1', '1.5.0-2', -1];
        yield ['1.5.0-1', '1.5.1-1', -1];
        yield ['1.5.0-2', '1.5.1-1', -1];

        // with pkgrel, mixed lengths
        yield ['1.5-1', '1.5.1-1', -1];
        yield ['1.5-2', '1.5.1-1', -1];
        yield ['1.5-2', '1.5.1-2', -1];

        // mixed pkgrel inclusion
        yield ['1.5', '1.5-1', 0];
        yield ['1.5-1', '1.5', 0];
        yield ['1.1-1', '1.1', 0];
        yield ['1.0-1', '1.1', -1];
        yield ['1.1-1', '1.0', 1];

        // alphanumeric versions
        yield ['1.5b-1', '1.5-1', -1];
        yield ['1.5b', '1.5', -1];
        yield ['1.5b-1', '1.5', -1];
        yield ['1.5b', '1.5.1', -1];

        // from the manpage
        yield ['1.0a', '1.0alpha', -1];
        yield ['1.0alpha', '1.0b', -1];
        yield ['1.0b', '1.0beta', -1];
        yield ['1.0beta', '1.0rc', -1];
        yield ['1.0rc', '1.0', -1];

        // going crazy? alpha-dotted versions
        yield ['1.5.a', '1.5', 1];
        yield ['1.5.b', '1.5.a', 1];
        yield ['1.5.1', '1.5.b', 1];

        // alpha dots and dashes
        yield ['1.5.b-1', '1.5.b', 0];
        yield ['1.5-1', '1.5.b', -1];

        // same/similar content, differing separators
        yield ['2.0', '2_0', 0];
        yield ['2.0_a', '2_0.a', 0];
        yield ['2.0a', '2.0.a', -1];
        yield ['2___a', '2_a', 1];

        // epoch included version comparisons
        yield ['0:1.0', '0:1.0', 0];
        yield ['0:1.0', '0:1.1', -1];
        yield ['1:1.0', '0:1.0', 1];
        yield ['1:1.0', '0:1.1', 1];
        yield ['1:1.0', '2:1.1', -1];

        // epoch + sometimes present pkgrel
        yield ['1:1.0', '0:1.0-1', 1];
        yield ['1:1.0-1', '0:1.1-1', 1];

        // epoch included on one version
        yield ['0:1.0', '1.0', 0];
        yield ['0:1.0', '1.1', -1];
        yield ['0:1.1', '1.0', 1];
        yield ['1:1.0', '1.0', 1];
        yield ['1:1.0', '1.1', 1];
        yield ['1:1.1', '1.1', 1];
    }

    #[DataProvider('provideVersionConstraints')]
    public function testSatisfies(string $providedVersion, string $requestedVersion, bool $expected): void
    {
        $this->assertEquals(
            $expected,
            $this->packageVersionCompare->satisfies(
                Version::createFromString($providedVersion),
                Version::createFromString($requestedVersion)
            )
        );
    }

    public static function provideVersionConstraints(): iterable
    {
        yield ['7.5-2', '>=7.5-1', true];
        yield ['=7.5', '<7.6', true];
        yield ['7.5', '>=7', true];
        yield ['7.5', '7', true];
        yield ['7.5', '8', false];
        yield ['7.5', '<7.5', false];
        yield ['7.5', '>7.5', false];
        yield ['3.0.7-2', '3.0.7', true];
        yield ['3.0.7-2', '3.0.7-1', true];
        yield ['3.0.7-2', '=3.0.7-1', false];
        yield ['3.0.7-2', '=3.0.7', true];
    }
}
