<?php

namespace App\Tests\Command\Update;

use App\Entity\Packages\Repository;
use App\Tests\Util\DatabaseTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \App\Command\Update\UpdateRepositoriesCommand
 */
class UpdateRepositoriesCommandTest extends DatabaseTestCase
{
    public function testUpdateRepositories()
    {
        $application = $this->createApplication();

        $command = $application->find('app:update:repositories');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);
        $this->assertEquals(0, $commandTester->getStatusCode());
        $this->assertCount(1, $this->getRepository(Repository::class)->findAll());
    }
}
