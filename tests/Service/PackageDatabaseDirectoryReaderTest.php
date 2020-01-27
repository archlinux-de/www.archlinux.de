<?php

namespace App\Tests\Service;

use App\Entity\Packages\Repository;
use App\Service\PackageDatabaseDirectoryReader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;

class PackageDatabaseDirectoryReaderTest extends TestCase
{
    public function testReadPackages(): void
    {
        /** @var SerializerInterface|MockObject $serializer */
        $serializer = $this->createMock(SerializerInterface::class);

        /** @var Repository|MockObject $repository */
        $repository = $this->createMock(Repository::class);

        /** @var \SplFileInfo|MockObject $packageDatabaseFile */
        $packageDatabaseFile = $this->createMock(\SplFileInfo::class);

        $packageDatabaseDirectoryReader = new PackageDatabaseDirectoryReader($serializer);
        $generator = $packageDatabaseDirectoryReader->readPackages($repository, $packageDatabaseFile);

        $packages = iterator_to_array($generator);

        $this->assertCount(0, $packages);
    }
}
