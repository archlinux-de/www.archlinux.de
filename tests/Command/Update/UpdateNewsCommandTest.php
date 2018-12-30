<?php

namespace App\Tests\Command\Update;

use App\Command\Update\UpdateNewsCommand;
use App\Entity\NewsItem;
use App\Repository\NewsItemRepository;
use App\Service\NewsItemFetcher;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @covers \App\Command\Update\UpdateNewsCommand
 */
class UpdateNewsCommandTest extends KernelTestCase
{
    public function testCommand()
    {
        $newNewsItem = (new NewsItem('2'))->setLastModified(new \DateTime('-1 day'));
        $oldNewsItem = (new NewsItem('1'))->setLastModified(new \DateTime('-2 day'));

        /** @var NewsItemRepository|MockObject $newsItemRepository */
        $newsItemRepository = $this->createMock(NewsItemRepository::class);
        $newsItemRepository->method('findAllExceptByIdsNewerThan')->willReturn([$oldNewsItem]);

        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('merge')->with($newNewsItem);
        $entityManager->expects($this->once())->method('remove')->with($oldNewsItem);
        $entityManager->expects($this->once())->method('flush');

        /** @var NewsItemFetcher|MockObject $newsItemFetcher */
        $newsItemFetcher = $this->createMock(NewsItemFetcher::class);
        $newsItemFetcher->method('getIterator')->willReturn(new \ArrayIterator([$newNewsItem]));

        /** @var ValidatorInterface|MockObject $validator */
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->atLeastOnce())->method('validate')->willReturn(new ConstraintViolationList());

        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(new UpdateNewsCommand($entityManager, $newsItemFetcher, $newsItemRepository, $validator));

        $command = $application->find('app:update:news');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testArchivedItemsAreKept()
    {
        $newNewsItem = (new NewsItem('2'))->setLastModified(new \DateTime('-1 day'));
        $oldNewsItem = (new NewsItem('1'))->setLastModified(new \DateTime('-2 day'));

        /** @var NewsItemRepository|MockObject $newsItemRepository */
        $newsItemRepository = $this->createMock(NewsItemRepository::class);
        $newsItemRepository
            ->method('findAllExceptByIdsNewerThan')
            ->with([$oldNewsItem->getId(), $newNewsItem->getId()], $oldNewsItem->getLastModified())
            ->willReturn([]);

        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->atLeastOnce())->method('merge');
        $entityManager->expects($this->never())->method('remove');
        $entityManager->expects($this->once())->method('flush');

        /** @var NewsItemFetcher|MockObject $newsItemFetcher */
        $newsItemFetcher = $this->createMock(NewsItemFetcher::class);
        $newsItemFetcher->method('getIterator')->willReturn(new \ArrayIterator([$oldNewsItem, $newNewsItem]));

        /** @var ValidatorInterface|MockObject $validator */
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->atLeastOnce())->method('validate')->willReturn(new ConstraintViolationList());

        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(new UpdateNewsCommand($entityManager, $newsItemFetcher, $newsItemRepository, $validator));

        $command = $application->find('app:update:news');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertEquals(0, $commandTester->getStatusCode());
    }
}
