<?php

namespace App\Tests\Command\Update;

use App\Command\Update\UpdateNewsCommand;
use App\Entity\NewsItem;
use App\Repository\NewsItemRepository;
use App\Service\NewsItemFetcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \App\Command\Update\UpdateNewsCommand
 */
class UpdateNewsCommandTest extends KernelTestCase
{
    public function testCommand()
    {
        $newNewsItem = (new NewsItem('2'))->setLastModified(new \DateTime('-1 day'));
        $oldNewsItem = (new NewsItem('1'))->setLastModified(new \DateTime('-2 day'));

        /** @var NewsItemRepository|\PHPUnit_Framework_MockObject_MockObject $newsItemRepository */
        $newsItemRepository = $this->createMock(NewsItemRepository::class);
        $newsItemRepository->method('findAllExceptByIdsNewerThan')->willReturn([$oldNewsItem]);

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('merge')->with($newNewsItem);
        $entityManager->expects($this->once())->method('remove')->with($oldNewsItem);
        $entityManager->expects($this->once())->method('flush');

        /** @var NewsItemFetcher|\PHPUnit_Framework_MockObject_MockObject $newsItemFetcher */
        $newsItemFetcher = $this->createMock(NewsItemFetcher::class);
        $newsItemFetcher->method('fetchNewsItems')->willReturn([$newNewsItem]);

        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(new UpdateNewsCommand($entityManager, $newsItemFetcher, $newsItemRepository));

        $command = $application->find('app:update:news');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testArchivedItemsAreKept()
    {
        $newNewsItem = (new NewsItem('2'))->setLastModified(new \DateTime('-1 day'));
        $oldNewsItem = (new NewsItem('1'))->setLastModified(new \DateTime('-2 day'));

        /** @var NewsItemRepository|\PHPUnit_Framework_MockObject_MockObject $newsItemRepository */
        $newsItemRepository = $this->createMock(NewsItemRepository::class);
        $newsItemRepository
            ->method('findAllExceptByIdsNewerThan')
            ->with([$oldNewsItem->getId(), $newNewsItem->getId()], $oldNewsItem->getLastModified())
            ->willReturn([]);

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->atLeastOnce())->method('merge');
        $entityManager->expects($this->never())->method('remove');
        $entityManager->expects($this->once())->method('flush');

        /** @var NewsItemFetcher|\PHPUnit_Framework_MockObject_MockObject $newsItemFetcher */
        $newsItemFetcher = $this->createMock(NewsItemFetcher::class);
        $newsItemFetcher->method('fetchNewsItems')->willReturn([$oldNewsItem, $newNewsItem]);

        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(new UpdateNewsCommand($entityManager, $newsItemFetcher, $newsItemRepository));

        $command = $application->find('app:update:news');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertEquals(0, $commandTester->getStatusCode());
    }
}
