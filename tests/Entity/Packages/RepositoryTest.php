<?php

namespace App\Tests\Entity\Packages;

use App\Entity\Packages\Architecture;
use App\Entity\Packages\Package;
use App\Entity\Packages\Repository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RepositoryTest extends TestCase
{
    /**
     * @param bool $testing
     * @dataProvider provideTesting
     */
    public function testJsonSerialize(bool $testing): void
    {
        $repository = new Repository('core', Architecture::X86_64);
        $repository->setTesting($testing);

        $json = (string)json_encode($repository);
        $this->assertJson($json);
        $jsonArray = json_decode($json, true);
        $this->assertEquals(
            [
                'name' => 'core',
                'architecture' => Architecture::X86_64,
                'testing' => $testing
            ],
            $jsonArray
        );
    }

    public function testMTime(): void
    {
        $mTime = new \DateTime();
        $repository = new Repository('core', Architecture::X86_64);
        $this->assertSame($repository, $repository->setMTime($mTime));
        $this->assertSame($mTime, $repository->getMTime());
    }

    public function testAddPackage(): void
    {
        /** @var Package|MockObject $package */
        $package = $this->createMock(Package::class);
        $repository = new Repository('core', Architecture::X86_64);
        $this->assertSame($repository, $repository->addPackage($package));
        $packages = $repository->getPackages();
        $this->assertEquals(1, $packages->count());
        $this->assertSame($package, $packages->first());
    }

    /**
     * @return array
     */
    public function provideTesting(): array
    {
        return [
            [true],
            [false]
        ];
    }
}
