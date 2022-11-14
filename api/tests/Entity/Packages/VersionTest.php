<?php

namespace App\Tests\Entity\Packages;

use App\Entity\Packages\Version;
use App\Entity\Packages\VersionConstraint;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class VersionTest extends TestCase
{
    #[DataProvider('provideVersions')]
    public function testCreateFromString(
        string $input,
        int $epoch,
        string $version,
        ?string $release,
        VersionConstraint $constraint
    ): void {
        $packageVersion = Version::createFromString($input);

        $this->assertSame($epoch, $packageVersion->getEpoch());
        $this->assertSame($version, $packageVersion->getVersion());
        $this->assertSame($release, $packageVersion->getRelease());
        $this->assertSame($constraint, $packageVersion->getConstraint());
    }

    public static function provideVersions(): iterable
    {
        yield ['1:2.3-4.5', 1, '2.3', '4.5', VersionConstraint::ANY];
        yield ['2.3-4.5', 0, '2.3', '4.5', VersionConstraint::ANY];
        yield ['2.3', 0, '2.3', null, VersionConstraint::ANY];
        yield ['2.3', 0, '2.3', null, VersionConstraint::ANY];
        yield ['=2.3', 0, '2.3', null, VersionConstraint::EQ];
        yield ['>2.3', 0, '2.3', null, VersionConstraint::GT];
        yield ['>=2.3', 0, '2.3', null, VersionConstraint::GE];
        yield ['<2.3', 0, '2.3', null, VersionConstraint::LT];
        yield ['<=2.3', 0, '2.3', null, VersionConstraint::LE];
        yield ['=libfoo-2.3.so-64', 0, 'libfoo-2.3.so', '64', VersionConstraint::EQ];
        yield ['=1-64', 0, '1', '64', VersionConstraint::EQ];
    }
}
