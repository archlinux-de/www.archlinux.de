<?php

namespace App\Tests\Command\Update;

use App\Command\Update\UpdateRepositoriesCommand;
use App\Repository\AbstractRelationRepository;
use App\Service\RepositoryManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(UpdateRepositoriesCommand::class)]
class UpdateRepositoriesCommandTest extends KernelTestCase
{
    public function testCommand(): void
    {
        /** @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        /** @var RepositoryManager&MockObject $repositoryManager */
        $repositoryManager = $this->createMock(RepositoryManager::class);
        $repositoryManager
            ->expects($this->once())
            ->method('createNewRepositories');
        $repositoryManager
            ->expects($this->once())
            ->method('removeObsoleteRepositories')
            ->willReturn(true);

        /** @var AbstractRelationRepository&MockObject $relationRepository */
        $relationRepository = $this->createMock(AbstractRelationRepository::class);
        $relationRepository
            ->expects($this->once())
            ->method('updateTargets');

        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->addCommand(
            new UpdateRepositoriesCommand($entityManager, $repositoryManager, $relationRepository)
        );

        $command = $application->find('app:update:repositories');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertEquals(0, $commandTester->getStatusCode());
    }
}
