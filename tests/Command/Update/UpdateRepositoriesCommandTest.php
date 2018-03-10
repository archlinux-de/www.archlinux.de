<?php

namespace App\Tests\Command\Update;

use App\Command\Update\UpdateRepositoriesCommand;
use App\Repository\AbstractRelationRepository;
use App\Service\RepositoryManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \App\Command\Update\UpdateRepositoriesCommand
 */
class UpdateRepositoriesCommandTest extends KernelTestCase
{
    public function testCommand()
    {
        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        /** @var RepositoryManager|\PHPUnit_Framework_MockObject_MockObject $repositoryManager */
        $repositoryManager = $this->createMock(RepositoryManager::class);
        $repositoryManager
            ->expects($this->once())
            ->method('createNewRepositories');
        $repositoryManager
            ->expects($this->once())
            ->method('removeObsoleteRepositories')
            ->willReturn(true);

        /** @var AbstractRelationRepository|\PHPUnit_Framework_MockObject_MockObject $relationRepository */
        $relationRepository = $this->createMock(AbstractRelationRepository::class);
        $relationRepository
            ->expects($this->once())
            ->method('updateTargets');

        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(new UpdateRepositoriesCommand($entityManager, $repositoryManager, $relationRepository));

        $command = $application->find('app:update:repositories');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertEquals(0, $commandTester->getStatusCode());
    }
}
