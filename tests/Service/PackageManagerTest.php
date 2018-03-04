<?php

namespace App\Tests\Service;

use App\ArchLinux\Package as DatabasePackage;
use App\ArchLinux\PackageDatabaseDownloader;
use App\Entity\Packages\Package;
use App\Entity\Packages\Repository;
use App\Repository\PackageRepository;
use App\Service\PackageManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class PackageManagerTest extends TestCase
{
    public function testDownloadPackagesForRepository()
    {
        /** @var Repository|\PHPUnit_Framework_MockObject_MockObject $repository */
        $repository = $this->createMock(Repository::class);

        /** @var DatabasePackage|\PHPUnit_Framework_MockObject_MockObject $databasePackage */
        $databasePackage = $this->createMock(DatabasePackage::class);

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($repository);

        /** @var \SplFileObject|\PHPUnit_Framework_MockObject_MockObject $packageDatabaseFile */
        $packageDatabaseFile = $this
            ->getMockBuilder(\SplFileObject::class)
            ->setConstructorArgs(['/dev/null'])
            ->getMock();
        $packageDatabaseFile
            ->method('getMTime')
            ->willReturn(1);

        /** @var PackageDatabaseDownloader|\PHPUnit_Framework_MockObject_MockObject $packageDatabaseDownloader */
        $packageDatabaseDownloader = $this->createMock(PackageDatabaseDownloader::class);
        $packageDatabaseDownloader
            ->expects($this->once())
            ->method('download')
            ->willReturn($packageDatabaseFile);
        $packageDatabaseDownloader
            ->expects($this->once())
            ->method('createDatabase')
            ->with($packageDatabaseFile)
            ->willReturn([$databasePackage]);

        /** @var PackageRepository|\PHPUnit_Framework_MockObject_MockObject $packageRepository */
        $packageRepository = $this->createMock(PackageRepository::class);

        $packageManager = new PackageManager($packageDatabaseDownloader, $entityManager, $packageRepository);
        $packageGenerator = $packageManager->downloadPackagesForRepository($repository);

        $packagesArray = iterator_to_array($packageGenerator);
        $this->assertCount(1, $packagesArray);
        $this->assertSame($databasePackage, $packagesArray[0]);
        $this->assertTrue($packageGenerator->getReturn());
    }

    public function testDownloadPackagesForRepositoryIsSkippedIfNoUpdatesAreAvailable()
    {
        /** @var Repository|\PHPUnit_Framework_MockObject_MockObject $repository */
        $repository = $this->createMock(Repository::class);
        $repository
            ->expects($this->atLeastOnce())
            ->method('getMTime')
            ->willReturn(new \DateTime());

        /** @var DatabasePackage|\PHPUnit_Framework_MockObject_MockObject $databasePackage */
        $databasePackage = $this->createMock(DatabasePackage::class);

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->never())
            ->method('persist')
            ->with($repository);

        /** @var \SplFileObject|\PHPUnit_Framework_MockObject_MockObject $packageDatabaseFile */
        $packageDatabaseFile = $this
            ->getMockBuilder(\SplFileObject::class)
            ->setConstructorArgs(['/dev/null'])
            ->getMock();
        $packageDatabaseFile
            ->method('getMTime')
            ->willReturn(1);

        /** @var PackageDatabaseDownloader|\PHPUnit_Framework_MockObject_MockObject $packageDatabaseDownloader */
        $packageDatabaseDownloader = $this->createMock(PackageDatabaseDownloader::class);
        $packageDatabaseDownloader
            ->expects($this->once())
            ->method('download')
            ->willReturn($packageDatabaseFile);
        $packageDatabaseDownloader
            ->expects($this->never())
            ->method('createDatabase')
            ->with($packageDatabaseFile)
            ->willReturn([$databasePackage]);

        /** @var PackageRepository|\PHPUnit_Framework_MockObject_MockObject $packageRepository */
        $packageRepository = $this->createMock(PackageRepository::class);

        $packageManager = new PackageManager($packageDatabaseDownloader, $entityManager, $packageRepository);
        $packageGenerator = $packageManager->downloadPackagesForRepository($repository);

        $packagesArray = iterator_to_array($packageGenerator);
        $this->assertCount(0, $packagesArray);
        $this->assertFalse($packageGenerator->getReturn());
    }

    public function testUpdatePackage()
    {
        /** @var Repository|\PHPUnit_Framework_MockObject_MockObject $repository */
        $repository = $this->createMock(Repository::class);
        $repository
            ->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn(1);

        /** @var DatabasePackage|\PHPUnit_Framework_MockObject_MockObject $databasePackage */
        $databasePackage = $this->createMock(DatabasePackage::class);

        /** @var Package|\PHPUnit_Framework_MockObject_MockObject $package */
        $package = $this->createMock(Package::class);
        $package
            ->expects($this->once())
            ->method('updateFromPackageDatabase')
            ->with($databasePackage);

        /** @var PackageDatabaseDownloader|\PHPUnit_Framework_MockObject_MockObject $packageDatabaseDownloader */
        $packageDatabaseDownloader = $this->createMock(PackageDatabaseDownloader::class);

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($package);

        /** @var PackageRepository|\PHPUnit_Framework_MockObject_MockObject $packageRepository */
        $packageRepository = $this->createMock(PackageRepository::class);
        $packageRepository
            ->expects($this->once())
            ->method('findByRepositoryAndName')
            ->willReturn($package);

        $packageManager = new PackageManager($packageDatabaseDownloader, $entityManager, $packageRepository);
        $this->assertTrue($packageManager->updatePackage($repository, $databasePackage));
    }

    public function testUpdateNewPackage()
    {
        /** @var Repository|\PHPUnit_Framework_MockObject_MockObject $repository */
        $repository = $this->createMock(Repository::class);
        $repository
            ->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn(1);

        /** @var DatabasePackage|\PHPUnit_Framework_MockObject_MockObject $databasePackage */
        $databasePackage = $this->createMock(DatabasePackage::class);
        $databasePackage
            ->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn('pacman');

        /** @var PackageDatabaseDownloader|\PHPUnit_Framework_MockObject_MockObject $packageDatabaseDownloader */
        $packageDatabaseDownloader = $this->createMock(PackageDatabaseDownloader::class);

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (Package $package) {
                $this->assertEquals('pacman', $package->getName());
                return true;
            }));

        /** @var PackageRepository|\PHPUnit_Framework_MockObject_MockObject $packageRepository */
        $packageRepository = $this->createMock(PackageRepository::class);
        $packageRepository
            ->expects($this->once())
            ->method('findByRepositoryAndName')
            ->willReturn(null);

        $packageManager = new PackageManager($packageDatabaseDownloader, $entityManager, $packageRepository);
        $this->assertTrue($packageManager->updatePackage($repository, $databasePackage));
    }

    public function testUpdatePackageIsSkippedWhenNoUpdatesAreAvailable()
    {
        /** @var Repository|\PHPUnit_Framework_MockObject_MockObject $repository */
        $repository = $this->createMock(Repository::class);
        $repository
            ->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn(1);

        /** @var DatabasePackage|\PHPUnit_Framework_MockObject_MockObject $databasePackage */
        $databasePackage = $this->createMock(DatabasePackage::class);

        /** @var PackageDatabaseDownloader|\PHPUnit_Framework_MockObject_MockObject $packageDatabaseDownloader */
        $packageDatabaseDownloader = $this->createMock(PackageDatabaseDownloader::class);

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->never())
            ->method('persist');

        /** @var PackageRepository|\PHPUnit_Framework_MockObject_MockObject $packageRepository */
        $packageRepository = $this->createMock(PackageRepository::class);
        $packageRepository
            ->expects($this->once())
            ->method('getMaxMTimeByRepository')
            ->willReturn(new \DateTime());

        $packageManager = new PackageManager($packageDatabaseDownloader, $entityManager, $packageRepository);
        $this->assertFalse($packageManager->updatePackage($repository, $databasePackage));
    }

    public function testCleanupObsoletePackages()
    {
        /** @var Repository|\PHPUnit_Framework_MockObject_MockObject $repository */
        $repository = $this->createMock(Repository::class);

        /** @var Package|\PHPUnit_Framework_MockObject_MockObject $package */
        $package = $this->createMock(Package::class);

        /** @var PackageDatabaseDownloader|\PHPUnit_Framework_MockObject_MockObject $packageDatabaseDownloader */
        $packageDatabaseDownloader = $this->createMock(PackageDatabaseDownloader::class);

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($package);

        /** @var PackageRepository|\PHPUnit_Framework_MockObject_MockObject $packageRepository */
        $packageRepository = $this->createMock(PackageRepository::class);
        $packageRepository
            ->expects($this->once())
            ->method('findByRepository')
            ->willReturn([$package]);

        $packageManager = new PackageManager($packageDatabaseDownloader, $entityManager, $packageRepository);
        $this->assertTrue($packageManager->cleanupObsoletePackages($repository, []));
    }

    public function testCleanupObsoletePackagesKeepsCurrentPackages()
    {
        $mTime = new \DateTime();

        /** @var Repository|\PHPUnit_Framework_MockObject_MockObject $repository */
        $repository = $this->createMock(Repository::class);
        $repository
            ->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn(1);

        /** @var Package|\PHPUnit_Framework_MockObject_MockObject $package */
        $package = $this->createMock(Package::class);
        $package
            ->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn('pacman');

        /** @var PackageDatabaseDownloader|\PHPUnit_Framework_MockObject_MockObject $packageDatabaseDownloader */
        $packageDatabaseDownloader = $this->createMock(PackageDatabaseDownloader::class);

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->never())
            ->method('remove');

        /** @var PackageRepository|\PHPUnit_Framework_MockObject_MockObject $packageRepository */
        $packageRepository = $this->createMock(PackageRepository::class);
        $packageRepository
            ->expects($this->once())
            ->method('findByRepositoryOlderThan')
            ->with($repository, $mTime)
            ->willReturn([$package]);
        $packageRepository
            ->expects($this->once())
            ->method('getMaxMTimeByRepository')
            ->willReturn($mTime);

        $packageManager = new PackageManager($packageDatabaseDownloader, $entityManager, $packageRepository);
        $this->assertFalse($packageManager->cleanupObsoletePackages($repository, [$package->getName()]));
    }
}
