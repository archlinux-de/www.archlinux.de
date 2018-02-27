<?php

namespace App\Tests\Command\Update;

use App\Command\Update\UpdateMirrorsCommand;
use App\Entity\Mirror;
use App\Repository\MirrorRepository;
use App\Service\MirrorFetcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \App\Command\Update\UpdateMirrorsCommand
 */
class UpdateMirrorsCommandTest extends KernelTestCase
{
    public function testCommand()
    {
        $newMirror = new Mirror('https://127.0.0.2', 'https');
        $oldMirror = new Mirror('https://127.0.0.1', 'https');

        /** @var MirrorRepository|\PHPUnit_Framework_MockObject_MockObject $mirrorRepository */
        $mirrorRepository = $this->createMock(MirrorRepository::class);
        $mirrorRepository->method('findAllExceptByUrls')->willReturn([$oldMirror]);

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('merge')->with($newMirror);
        $entityManager->expects($this->once())->method('remove')->with($oldMirror);
        $entityManager->expects($this->once())->method('flush');

        /** @var MirrorFetcher|\PHPUnit_Framework_MockObject_MockObject $mirrorFetcher */
        $mirrorFetcher = $this->createMock(MirrorFetcher::class);
        $mirrorFetcher->method('getIterator')->willReturn(new \ArrayIterator([$newMirror]));

        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(new UpdateMirrorsCommand($entityManager, $mirrorFetcher, $mirrorRepository));

        $command = $application->find('app:update:mirrors');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertEquals(0, $commandTester->getStatusCode());
    }
}
