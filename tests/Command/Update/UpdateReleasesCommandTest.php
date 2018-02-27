<?php

namespace App\Tests\Command\Update;

use App\Command\Update\UpdateReleasesCommand;
use App\Entity\Release;
use App\Repository\ReleaseRepository;
use App\Service\ReleaseFetcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \App\Command\Update\UpdateReleasesCommand
 */
class UpdateReleasesCommandTest extends KernelTestCase
{
    public function testCommand()
    {
        $newRelease = new Release('2');
        $oldRelease = new Release('1');

        /** @var ReleaseRepository|\PHPUnit_Framework_MockObject_MockObject $releaseRepository */
        $releaseRepository = $this->createMock(ReleaseRepository::class);
        $releaseRepository->method('findAllExceptByVersions')->willReturn([$oldRelease]);

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('merge')->with($newRelease);
        $entityManager->expects($this->once())->method('remove')->with($oldRelease);
        $entityManager->expects($this->once())->method('flush');

        /** @var ReleaseFetcher|\PHPUnit_Framework_MockObject_MockObject $releaseFetcher */
        $releaseFetcher = $this->createMock(ReleaseFetcher::class);
        $releaseFetcher->method('getIterator')->willReturn(new \ArrayIterator([$newRelease]));

        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(new UpdateReleasesCommand($entityManager, $releaseFetcher, $releaseRepository));

        $command = $application->find('app:update:releases');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertEquals(0, $commandTester->getStatusCode());
    }
}
