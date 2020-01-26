<?php

namespace App\Tests\Serializer;

use App\Entity\Packages\Package;
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
                ['repository' => new Repository('', '')]
            )
        );
    }

    public function testDenormalize(): void
    {
        $repository = new Repository('', '');
        $packageDenormalizer = new PackageDenormalizer();

        $package = $packageDenormalizer->denormalize(
            [
                'NAME' => 'pacman',
                'VERSION' => '',
                'ARCH' => '',
                'FILENAME' => '',
                'URL' => '',
                'DESC' => '',
                'BASE' => '',
                'BUILDDATE' => 0,
                'CSIZE' => 0,
                'ISIZE' => 0,
                'MD5SUM' => '',
                'PACKAGER' => '',
                'SHA256SUM' => '',
                'PGPSIG' => '',
                'LICENSE' => '',
                'GROUPS' => '',
                'FILES' => '',
                'DEPENDS' => '',
                'CONFLICTS' => '',
                'REPLACES' => '',
                'OPTDEPENDS' => '',
                'PROVIDES' => '',
                'MAKEDEPENDS' => '',
                'CHECKDEPENDS' => '',
            ],
            Package::class,
            'pacman-database',
            ['repository' => $repository]
        );

        $this->assertEquals('pacman', $package->getName());
    }
}
