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
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @covers \App\Command\Update\UpdatePackagesCommand
 */
class UpdatePackagesCommandTest extends KernelTestCase
{
    public function testCommand()
    {
        /** @var Repository|MockObject $repository */
        $repository = $this->createMock(Repository::class);

        /** @var DatabasePackage|MockObject $package */
        $package = $this->createMock(DatabasePackage::class);
        $package
            ->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn('foo');

        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->atLeastOnce())->method('flush');

        /** @var PackageDatabaseMirror|MockObject $packageDatabaseMirror */
        $packageDatabaseMirror = $this->createMock(PackageDatabaseMirror::class);
        $packageDatabaseMirror->expects($this->once())->method('hasUpdated')->willReturn(true);

        /** @var RepositoryRepository|MockObject $repositoryRepository */
        $repositoryRepository = $this->createMock(RepositoryRepository::class);
        $repositoryRepository->expects($this->once())->method('findAll')->willReturn([$repository]);

        /** @var AbstractRelationRepository|MockObject $relationRepository */
        $relationRepository = $this->createMock(AbstractRelationRepository::class);

        /** @var PackageManager|MockObject $packageManager */
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager
            ->expects($this->once())
            ->method('downloadPackagesForRepository')
            ->with($repository)
            ->willReturn($this->createGenerator([$package]));
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

        /** @var ValidatorInterface|MockObject $validator */
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->atLeastOnce())->method('validate')->willReturn(new ConstraintViolationList());

        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(new UpdatePackagesCommand(
            $entityManager,
            $packageDatabaseMirror,
            $repositoryRepository,
            $relationRepository,
            $packageManager,
            $validator
        ));

        $command = $application->find('app:update:packages');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    /**
     * @param iterable $iterable
     * @return \Generator
     */
    private function createGenerator(iterable $iterable): \Generator
    {
        foreach ($iterable as $item) {
            yield $item;
        }
        return true;
    }
}
