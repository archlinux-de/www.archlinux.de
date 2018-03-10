<?php

namespace App\Tests\Command\Update;

use App\ArchLinux\Package as DatabasePackage;
use App\ArchLinux\PackageDatabaseMirror;
use App\Command\Update\UpdatePackagesCommand;
use App\Entity\Packages\Repository;
use App\Repository\AbstractRelationRepository;
use App\Repository\RepositoryRepository;
use App\Service\PackageManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \App\Command\Update\UpdatePackagesCommand
 */
class UpdatePackagesCommandTest extends KernelTestCase
{
    public function testCommand()
    {
        /** @var Repository|\PHPUnit_Framework_MockObject_MockObject $repository */
        $repository = $this->createMock(Repository::class);

        /** @var DatabasePackage|\PHPUnit_Framework_MockObject_MockObject $package */
        $package = $this->createMock(DatabasePackage::class);
        $package
            ->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn('foo');

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->atLeastOnce())->method('flush');

        /** @var PackageDatabaseMirror|\PHPUnit_Framework_MockObject_MockObject $packageDatabaseMirror */
        $packageDatabaseMirror = $this->createMock(PackageDatabaseMirror::class);
        $packageDatabaseMirror->expects($this->once())->method('hasUpdated')->willReturn(true);

        /** @var RepositoryRepository|\PHPUnit_Framework_MockObject_MockObject $repositoryRepository */
        $repositoryRepository = $this->createMock(RepositoryRepository::class);
        $repositoryRepository->expects($this->once())->method('findAll')->willReturn([$repository]);

        /** @var AbstractRelationRepository|\PHPUnit_Framework_MockObject_MockObject $relationRepository */
        $relationRepository = $this->createMock(AbstractRelationRepository::class);

        /** @var PackageManager|\PHPUnit_Framework_MockObject_MockObject $packageManager */
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager
            ->expects($this->once())
            ->method('downloadPackagesForRepository')
            ->with($repository)
            ->willReturn($this->createGenerator([$package], true));
        $packageManager
            ->expects($this->once())
            ->method('updatePackage')
            ->with($repository, $package)
            ->willReturn(true);
        $packageManager
            ->expects($this->once())
            ->method('cleanupObsoletePackages')
            ->with($repository, [$package->getName()])
            ->willReturn(true);

        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(new UpdatePackagesCommand(
            $entityManager,
            $packageDatabaseMirror,
            $repositoryRepository,
            $relationRepository,
            $packageManager
        ));

        $command = $application->find('app:update:packages');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    /**
     * @param iterable $iterable
     * @param $return
     * @return \Generator
     */
    private function createGenerator(iterable $iterable, $return): \Generator
    {
        foreach ($iterable as $item) {
            yield $item;
        }
        return $return;
    }
}
