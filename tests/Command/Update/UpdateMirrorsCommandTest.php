<?php

namespace App\Tests\Command\Update;

use App\Command\Update\UpdateMirrorsCommand;
use App\Entity\Mirror;
use App\Repository\MirrorRepository;
use App\Service\MirrorFetcher;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @covers \App\Command\Update\UpdateMirrorsCommand
 */
class UpdateMirrorsCommandTest extends KernelTestCase
{
    public function testCommand()
    {
        $newMirror = new Mirror('https://127.0.0.2', 'https');
        $oldMirror = new Mirror('https://127.0.0.1', 'https');

        /** @var MirrorRepository|MockObject $mirrorRepository */
        $mirrorRepository = $this->createMock(MirrorRepository::class);
        $mirrorRepository->method('findAllExceptByUrls')->willReturn([$oldMirror]);

        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('merge')->with($newMirror);
        $entityManager->expects($this->once())->method('remove')->with($oldMirror);
        $entityManager->expects($this->once())->method('flush');

        /** @var MirrorFetcher|MockObject $mirrorFetcher */
        $mirrorFetcher = $this->createMock(MirrorFetcher::class);
        $mirrorFetcher->method('getIterator')->willReturn(new \ArrayIterator([$newMirror]));

        /** @var ValidatorInterface|MockObject $validator */
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->atLeastOnce())->method('validate')->willReturn(new ConstraintViolationList());

        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(new UpdateMirrorsCommand($entityManager, $mirrorFetcher, $mirrorRepository, $validator));

        $command = $application->find('app:update:mirrors');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertEquals(0, $commandTester->getStatusCode());
    }
}
