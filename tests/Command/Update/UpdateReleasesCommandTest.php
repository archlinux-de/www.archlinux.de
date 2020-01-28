<?php

namespace App\Tests\Command\Update;

use App\Command\Exception\ValidationException;
use App\Command\Update\UpdateReleasesCommand;
use App\Entity\Release;
use App\Entity\Torrent;
use App\Repository\ReleaseRepository;
use App\Service\ReleaseFetcher;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @covers \App\Command\Update\UpdateReleasesCommand
 */
class UpdateReleasesCommandTest extends KernelTestCase
{
    public function testCommand(): void
    {
        $newRelease = new Release('2');
        $oldRelease = new Release('1');

        /** @var ReleaseRepository|MockObject $releaseRepository */
        $releaseRepository = $this->createMock(ReleaseRepository::class);
        $releaseRepository->method('findAllExceptByVersions')->willReturn([$oldRelease]);

        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist')->with($newRelease);
        $entityManager->expects($this->once())->method('remove')->with($oldRelease);
        $entityManager->expects($this->once())->method('flush');

        /** @var ReleaseFetcher|MockObject $releaseFetcher */
        $releaseFetcher = $this->createMock(ReleaseFetcher::class);
        $releaseFetcher->method('getIterator')->willReturn(new \ArrayIterator([$newRelease]));

        /** @var ValidatorInterface|MockObject $validator */
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->atLeastOnce())->method('validate')->willReturn(new ConstraintViolationList());

        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(new UpdateReleasesCommand($entityManager, $releaseFetcher, $releaseRepository, $validator));

        $command = $application->find('app:update:releases');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testUpdateFailsOnInvalidRelease(): void
    {
        /** @var ReleaseRepository|MockObject $releaseRepository */
        $releaseRepository = $this->createMock(ReleaseRepository::class);

        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');

        /** @var ReleaseFetcher|MockObject $releaseFetcher */
        $releaseFetcher = $this->createMock(ReleaseFetcher::class);
        $releaseFetcher->method('getIterator')->willReturn(new \ArrayIterator([new Release('2')]));

        /** @var ValidatorInterface|MockObject $validator */
        $validator = $this->createMock(ValidatorInterface::class);
        $validator
            ->expects($this->atLeastOnce())
            ->method('validate')
            ->willReturn(new ConstraintViolationList([$this->createMock(ConstraintViolation::class)]));

        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(new UpdateReleasesCommand($entityManager, $releaseFetcher, $releaseRepository, $validator));

        $command = $application->find('app:update:releases');
        $commandTester = new CommandTester($command);
        $this->expectException(ValidationException::class);
        $commandTester->execute(['command' => $command->getName()]);
    }

    public function testUpdateRelease(): void
    {
        $release = (new Release('2'))
            ->setAvailable(true)
            ->setCreated(new \DateTime())
            ->setInfo('')
            ->setIsoUrl('')
            ->setReleaseDate(new \DateTime())
            ->setTorrent(new Torrent());

        /** @var ReleaseRepository|MockObject $releaseRepository */
        $releaseRepository = $this->createMock(ReleaseRepository::class);
        $releaseRepository->expects($this->once())->method('find')->with($release->getVersion())->willReturn($release);

        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        /** @var ReleaseFetcher|MockObject $releaseFetcher */
        $releaseFetcher = $this->createMock(ReleaseFetcher::class);
        $releaseFetcher->method('getIterator')->willReturn(new \ArrayIterator([$release]));

        /** @var ValidatorInterface|MockObject $validator */
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->atLeastOnce())->method('validate')->willReturn(new ConstraintViolationList());

        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(new UpdateReleasesCommand($entityManager, $releaseFetcher, $releaseRepository, $validator));

        $command = $application->find('app:update:releases');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertEquals(0, $commandTester->getStatusCode());
    }
}
