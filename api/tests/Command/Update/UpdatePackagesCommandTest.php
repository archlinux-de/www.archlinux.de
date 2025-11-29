<?php

namespace App\Tests\Command\Update;

use App\Command\Update\UpdatePackagesCommand;
use App\Entity\Packages\Package;
use App\Entity\Packages\Repository;
use App\Repository\AbstractRelationRepository;
use App\Repository\PackageRepository;
use App\Repository\RepositoryRepository;
use App\Service\PackageDatabaseDownloader;
use App\Service\PackageDatabaseMirror;
use App\Service\PackageDatabaseReader;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[CoversClass(UpdatePackagesCommand::class)]
class UpdatePackagesCommandTest extends KernelTestCase
{
    public function testCommand(): void
    {
        /** @var Repository&MockObject $repository */
        $repository = $this->createMock(Repository::class);

        /** @var Package&MockObject $packageA */
        $packageA = $this->createMock(Package::class);
        $packageA
            ->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn('packageA');
        $packageA
            ->expects($this->atLeastOnce())
            ->method('getSha256sum')
            ->willReturn('abc', 'def');

        /** @var Package&MockObject $packageB */
        $packageB = $this->createMock(Package::class);
        $packageB
            ->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn('packageB');

        /** @var Package&MockObject $packageC */
        $packageC = $this->createMock(Package::class);

        /** @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->atLeastOnce())->method('flush');

        /** @var PackageDatabaseMirror&MockObject $packageDatabaseMirror */
        $packageDatabaseMirror = $this->createMock(PackageDatabaseMirror::class);
        $packageDatabaseMirror->expects($this->once())->method('hasUpdated')->willReturn(true);

        /** @var RepositoryRepository&MockObject $repositoryRepository */
        $repositoryRepository = $this->createMock(RepositoryRepository::class);
        $repositoryRepository->expects($this->once())->method('findAll')->willReturn([$repository]);

        /** @var AbstractRelationRepository&MockObject $relationRepository */
        $relationRepository = $this->createMock(AbstractRelationRepository::class);

        $packageDatabase = '';

        /** @var PackageDatabaseReader&MockObject $packageDatabaseReader */
        $packageDatabaseReader = $this->createMock(PackageDatabaseReader::class);
        $packageDatabaseReader
            ->expects($this->once())
            ->method('readPackages')
            ->with($repository, $packageDatabase)
            ->willReturn($this->createGenerator([$packageA, $packageB]));

        /** @var PackageDatabaseDownloader&MockObject $packageDatabaseDownloader */
        $packageDatabaseDownloader = $this->createMock(PackageDatabaseDownloader::class);
        $packageDatabaseDownloader
            ->expects($this->once())
            ->method('download')
            ->with($repository->getName(), $repository->getArchitecture())
            ->willReturn($packageDatabase);

        /** @var PackageRepository&MockObject $packageRepository */
        $packageRepository = $this->createMock(PackageRepository::class);
        $packageRepository
            ->expects($this->atLeastOnce())
            ->method('findByRepositoryAndName')
            ->willReturn($packageA, null);
        $packageRepository
            ->expects($this->once())
            ->method('findByRepositoryExceptNames')
            ->willReturn([$packageC]);

        /** @var ValidatorInterface&MockObject $validator */
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->atLeastOnce())->method('validate')->willReturn(new ConstraintViolationList());

        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->addCommand(
            new UpdatePackagesCommand(
                $entityManager,
                $packageDatabaseMirror,
                $repositoryRepository,
                $relationRepository,
                $packageDatabaseReader,
                $validator,
                $packageDatabaseDownloader,
                $packageRepository
            )
        );

        $command = $application->find('app:update:packages');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    /**
     * @param iterable<mixed> $iterable
     */
    private function createGenerator(iterable $iterable): \Generator
    {
        foreach ($iterable as $item) {
            yield $item;
        }
        return true;
    }

    public function testUpdateFailsOnInvalidPackage(): void
    {
        /** @var Repository&MockObject $repository */
        $repository = $this->createMock(Repository::class);

        /** @var Package&MockObject $package */
        $package = $this->createMock(Package::class);

        /** @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');

        /** @var PackageDatabaseMirror&MockObject $packageDatabaseMirror */
        $packageDatabaseMirror = $this->createMock(PackageDatabaseMirror::class);
        $packageDatabaseMirror->expects($this->once())->method('hasUpdated')->willReturn(true);

        /** @var RepositoryRepository&MockObject $repositoryRepository */
        $repositoryRepository = $this->createMock(RepositoryRepository::class);
        $repositoryRepository->expects($this->once())->method('findAll')->willReturn([$repository]);

        /** @var AbstractRelationRepository&MockObject $relationRepository */
        $relationRepository = $this->createMock(AbstractRelationRepository::class);

        $packageDatabase = '';

        /** @var PackageDatabaseReader&MockObject $packageDatabaseReader */
        $packageDatabaseReader = $this->createMock(PackageDatabaseReader::class);
        $packageDatabaseReader
            ->expects($this->once())
            ->method('readPackages')
            ->with($repository, $packageDatabase)
            ->willReturn($this->createGenerator([$package]));

        /** @var PackageDatabaseDownloader&MockObject $packageDatabaseDownloader */
        $packageDatabaseDownloader = $this->createMock(PackageDatabaseDownloader::class);
        $packageDatabaseDownloader
            ->expects($this->once())
            ->method('download')
            ->with($repository->getName(), $repository->getArchitecture())
            ->willReturn($packageDatabase);

        /** @var PackageRepository&MockObject $packageRepository */
        $packageRepository = $this->createMock(PackageRepository::class);

        /** @var ValidatorInterface&MockObject $validator */
        $validator = $this->createMock(ValidatorInterface::class);
        $validator
            ->expects($this->atLeastOnce())
            ->method('validate')
            ->willReturn(new ConstraintViolationList([$this->createMock(ConstraintViolation::class)]));

        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->addCommand(
            new UpdatePackagesCommand(
                $entityManager,
                $packageDatabaseMirror,
                $repositoryRepository,
                $relationRepository,
                $packageDatabaseReader,
                $validator,
                $packageDatabaseDownloader,
                $packageRepository
            )
        );

        $command = $application->find('app:update:packages');
        $commandTester = new CommandTester($command);
        $this->expectException(ValidationFailedException::class);
        $commandTester->execute(['command' => $command->getName()]);
    }
}
