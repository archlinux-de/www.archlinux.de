<?php

namespace App\Tests\Service;

use App\ArchLinux\Package as DatabasePackage;
use App\ArchLinux\PackageDatabaseDownloader;
use App\Entity\Packages\Package;
use App\Entity\Packages\Repository;
use App\Repository\PackageRepository;
use App\Service\PackageManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PackageManagerTest extends TestCase
{
    public function testDownloadPackagesForRepository(): void
    {
        /** @var Repository|MockObject $repository */
        $repository = $this->createMock(Repository::class);

        /** @var DatabasePackage|MockObject $databasePackage */
        $databasePackage = $this->createMock(DatabasePackage::class);

        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($repository);

        /** @var \SplFileObject|MockObject $packageDatabaseFile */
        $packageDatabaseFile = $this
            ->getMockBuilder(\SplFileObject::class)
            ->setConstructorArgs(['/dev/null'])
            ->getMock();
        $packageDatabaseFile
            ->method('getRealPath')
            ->willReturn('/dev/null');

        /** @var PackageDatabaseDownloader|MockObject $packageDatabaseDownloader */
        $packageDatabaseDownloader = $this->createMock(PackageDatabaseDownloader::class);
        $packageDatabaseDownloader
            ->expects($this->once())
            ->method('download')
            ->willReturn($packageDatabaseFile);
        $packageDatabaseDownloader
            ->expects($this->once())
            ->method('createDatabase')
            ->with($packageDatabaseFile)
            ->willReturn(new \ArrayObject([$databasePackage]));

        /** @var PackageRepository|MockObject $packageRepository */
        $packageRepository = $this->createMock(PackageRepository::class);

        $packageManager = new PackageManager($packageDatabaseDownloader, $entityManager, $packageRepository);
        $packageGenerator = $packageManager->downloadPackagesForRepository($repository);

        $packagesArray = iterator_to_array($packageGenerator);
        $this->assertCount(1, $packagesArray);
        $this->assertSame($databasePackage, $packagesArray[0]);
        $this->assertTrue($packageGenerator->getReturn());
    }

    public function testDownloadPackagesForRepositoryIsSkippedIfNoUpdatesAreAvailable(): void
    {
        /** @var Repository|MockObject $repository */
        $repository = $this->createMock(Repository::class);
        $repository
            ->expects($this->atLeastOnce())
            ->method('getSha256sum')
            ->willReturn(hash('sha256', ''));

        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->never())
            ->method('persist')
            ->with($repository);

        /** @var \SplFileObject|MockObject $packageDatabaseFile */
        $packageDatabaseFile = $this
            ->getMockBuilder(\SplFileObject::class)
            ->setConstructorArgs(['/dev/null'])
            ->getMock();
        $packageDatabaseFile
            ->method('getRealPath')
            ->willReturn('/dev/null');

        /** @var PackageDatabaseDownloader|MockObject $packageDatabaseDownloader */
        $packageDatabaseDownloader = $this->createMock(PackageDatabaseDownloader::class);
        $packageDatabaseDownloader
            ->expects($this->once())
            ->method('download')
            ->willReturn($packageDatabaseFile);
        $packageDatabaseDownloader
            ->expects($this->never())
            ->method('createDatabase');

        /** @var PackageRepository|MockObject $packageRepository */
        $packageRepository = $this->createMock(PackageRepository::class);

        $packageManager = new PackageManager($packageDatabaseDownloader, $entityManager, $packageRepository);
        $packageGenerator = $packageManager->downloadPackagesForRepository($repository);

        $packagesArray = iterator_to_array($packageGenerator);
        $this->assertCount(0, $packagesArray);
        $this->assertFalse($packageGenerator->getReturn());
    }

    public function testUpdatePackage(): void
    {
        /** @var Repository|MockObject $repository */
        $repository = $this->createMock(Repository::class);

        /** @var DatabasePackage|MockObject $databasePackage */
        $databasePackage = $this->createMock(DatabasePackage::class);
        $databasePackage
            ->expects($this->once())
            ->method('getSha256sum')
            ->willReturn('bar');

        /** @var Package|MockObject $package */
        $package = $this->createMock(Package::class);
        $package
            ->expects($this->once())
            ->method('updateFromPackageDatabase')
            ->with($databasePackage);
        $package
            ->expects($this->once())
            ->method('getSha256sum')
            ->willReturn('foo');

        /** @var PackageDatabaseDownloader|MockObject $packageDatabaseDownloader */
        $packageDatabaseDownloader = $this->createMock(PackageDatabaseDownloader::class);

        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($package);

        /** @var PackageRepository|MockObject $packageRepository */
        $packageRepository = $this->createMock(PackageRepository::class);
        $packageRepository
            ->expects($this->once())
            ->method('findByRepositoryAndName')
            ->willReturn($package);

        $packageManager = new PackageManager($packageDatabaseDownloader, $entityManager, $packageRepository);
        $this->assertTrue($packageManager->updatePackage($repository, $databasePackage));
    }

    public function testUpdateNewPackage(): void
    {
        /** @var Repository|MockObject $repository */
        $repository = $this->createMock(Repository::class);

        /** @var DatabasePackage|MockObject $databasePackage */
        $databasePackage = $this->createMock(DatabasePackage::class);
        $databasePackage
            ->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn('pacman');

        /** @var PackageDatabaseDownloader|MockObject $packageDatabaseDownloader */
        $packageDatabaseDownloader = $this->createMock(PackageDatabaseDownloader::class);

        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with(
                $this->callback(
                    function (Package $package) {
                        $this->assertEquals('pacman', $package->getName());
                        return true;
                    }
                )
            );

        /** @var PackageRepository|MockObject $packageRepository */
        $packageRepository = $this->createMock(PackageRepository::class);
        $packageRepository
            ->expects($this->once())
            ->method('findByRepositoryAndName')
            ->willReturn(null);

        $packageManager = new PackageManager($packageDatabaseDownloader, $entityManager, $packageRepository);
        $this->assertTrue($packageManager->updatePackage($repository, $databasePackage));
    }

    public function testUpdatePackageIsSkippedWhenNoUpdatesAreAvailable(): void
    {
        /** @var Repository|MockObject $repository */
        $repository = $this->createMock(Repository::class);

        /** @var DatabasePackage|MockObject $databasePackage */
        $databasePackage = $this->createMock(DatabasePackage::class);
        $databasePackage
            ->expects($this->once())
            ->method('getSha256sum')
            ->willReturn('foo');

        /** @var Package|MockObject $package */
        $package = $this->createMock(Package::class);
        $package
            ->expects($this->never())
            ->method('updateFromPackageDatabase')
            ->with($databasePackage);
        $package
            ->expects($this->once())
            ->method('getSha256sum')
            ->willReturn('foo');

        /** @var PackageDatabaseDownloader|MockObject $packageDatabaseDownloader */
        $packageDatabaseDownloader = $this->createMock(PackageDatabaseDownloader::class);

        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->never())
            ->method('persist');

        /** @var PackageRepository|MockObject $packageRepository */
        $packageRepository = $this->createMock(PackageRepository::class);
        $packageRepository
            ->expects($this->once())
            ->method('findByRepositoryAndName')
            ->willReturn($package);

        $packageManager = new PackageManager($packageDatabaseDownloader, $entityManager, $packageRepository);
        $this->assertFalse($packageManager->updatePackage($repository, $databasePackage));
    }

    public function testCleanupObsoletePackages(): void
    {
        /** @var Repository|MockObject $repository */
        $repository = $this->createMock(Repository::class);

        /** @var Package|MockObject $package */
        $package = $this->createMock(Package::class);

        /** @var PackageDatabaseDownloader|MockObject $packageDatabaseDownloader */
        $packageDatabaseDownloader = $this->createMock(PackageDatabaseDownloader::class);

        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($package);

        /** @var PackageRepository|MockObject $packageRepository */
        $packageRepository = $this->createMock(PackageRepository::class);
        $packageRepository
            ->expects($this->once())
            ->method('findByRepositoryExceptNames')
            ->willReturn([$package]);

        $packageManager = new PackageManager($packageDatabaseDownloader, $entityManager, $packageRepository);
        $this->assertTrue($packageManager->cleanupObsoletePackages($repository, []));
    }

    public function testCleanupObsoletePackagesKeepsCurrentPackages(): void
    {
        /** @var Repository|MockObject $repository */
        $repository = $this->createMock(Repository::class);

        /** @var Package|MockObject $package */
        $package = $this->createMock(Package::class);
        $package
            ->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn('pacman');

        /** @var PackageDatabaseDownloader|MockObject $packageDatabaseDownloader */
        $packageDatabaseDownloader = $this->createMock(PackageDatabaseDownloader::class);

        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->never())
            ->method('remove');

        /** @var PackageRepository|MockObject $packageRepository */
        $packageRepository = $this->createMock(PackageRepository::class);
        $packageRepository
            ->expects($this->once())
            ->method('findByRepositoryExceptNames')
            ->with($repository)
            ->willReturn([]);

        $packageManager = new PackageManager($packageDatabaseDownloader, $entityManager, $packageRepository);
        $this->assertFalse($packageManager->cleanupObsoletePackages($repository, [$package->getName()]));
    }
}
