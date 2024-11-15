<?php

namespace App\Tests\Service;

use App\Entity\Packages\Package;
use App\Entity\Packages\Repository;
use App\Service\PackageDatabaseExtractor;
use App\Service\PackageDatabaseReader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;

class PackageDatabaseReaderTest extends TestCase
{
    public function testReadPackages(): void
    {
        $repository = $this->createMock(Repository::class);

        $package = $this->createMock(Package::class);

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('deserialize')
            ->willReturn($package);

        $packageDatabaseExtractor = $this->createMock(PackageDatabaseExtractor::class);
        $packageDatabaseExtractor
            ->expects($this->once())
            ->method('extractPackageDescriptions')
            ->willReturn(new \ArrayIterator(['']));

        $packageDatabase = '';

        $packageDatabaseReader = new PackageDatabaseReader($serializer, $packageDatabaseExtractor);
        $generator = $packageDatabaseReader->readPackages($repository, $packageDatabase);

        $packages = [...$generator];

        $this->assertCount(1, $packages);
    }
}
