<?php

namespace App\Tests\Command\Update;

use App\Command\Update\UpdateMirrorsCommand;
use App\Entity\Mirror;
use App\Repository\MirrorRepository;
use App\Service\MirrorFetcher;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @covers \App\Command\Update\UpdateMirrorsCommand
 */
class UpdateMirrorsCommandTest extends KernelTestCase
{
    public function testCommand(): void
    {
        $newMirror = new Mirror('https://127.0.0.2', 'https');
        $oldMirror = new Mirror('https://127.0.0.1', 'https');

        /** @var MirrorRepository|MockObject $mirrorRepository */
        $mirrorRepository = $this->createMock(MirrorRepository::class);
        $mirrorRepository->method('findAllExceptByUrls')->willReturn([$oldMirror]);

        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist')->with($newMirror);
        $entityManager->expects($this->once())->method('remove')->with($oldMirror);
        $entityManager->expects($this->once())->method('flush');

        /** @var MirrorFetcher|MockObject $mirrorFetcher */
        $mirrorFetcher = $this->createMock(MirrorFetcher::class);
        $mirrorFetcher->method('getIterator')->willReturn(new \ArrayIterator([$newMirror]));

        /** @var ValidatorInterface|MockObject $validator */
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->atLeastOnce())->method('validate')->willReturn(new ConstraintViolationList());

        $logger = $this->createMock(LoggerInterface::class);

        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(
            new UpdateMirrorsCommand($entityManager, $mirrorFetcher, $mirrorRepository, $validator, $logger)
        );

        $command = $application->find('app:update:mirrors');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testIgnoreInvalidMirrors(): void
    {
        /** @var MirrorRepository|MockObject $mirrorRepository */
        $mirrorRepository = $this->createMock(MirrorRepository::class);
        $mirrorRepository->expects($this->once())->method('findAllExceptByUrls')->with([]);

        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        /** @var MirrorFetcher|MockObject $mirrorFetcher */
        $mirrorFetcher = $this->createMock(MirrorFetcher::class);
        $mirrorFetcher
            ->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([new Mirror('%invslid', 'https')]));

        /** @var ValidatorInterface|MockObject $validator */
        $validator = $this->createMock(ValidatorInterface::class);
        $validator
            ->expects($this->atLeastOnce())
            ->method('validate')
            ->willReturn(new ConstraintViolationList([$this->createMock(ConstraintViolation::class)]));

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('error');

        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(
            new UpdateMirrorsCommand($entityManager, $mirrorFetcher, $mirrorRepository, $validator, $logger)
        );

        $command = $application->find('app:update:mirrors');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);
    }

    public function testUpdateMirror(): void
    {
        $mirror = new Mirror('https://127.0.0.2', 'https');

        /** @var MirrorRepository|MockObject $mirrorRepository */
        $mirrorRepository = $this->createMock(MirrorRepository::class);
        $mirrorRepository->expects($this->once())->method('find')->with($mirror->getUrl())->willReturn($mirror);

        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        /** @var MirrorFetcher|MockObject $mirrorFetcher */
        $mirrorFetcher = $this->createMock(MirrorFetcher::class);
        $mirrorFetcher->method('getIterator')->willReturn(new \ArrayIterator([$mirror]));

        /** @var ValidatorInterface|MockObject $validator */
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->atLeastOnce())->method('validate')->willReturn(new ConstraintViolationList());

        $logger = $this->createMock(LoggerInterface::class);

        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(
            new UpdateMirrorsCommand($entityManager, $mirrorFetcher, $mirrorRepository, $validator, $logger)
        );

        $command = $application->find('app:update:mirrors');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertEquals(0, $commandTester->getStatusCode());
    }
}
