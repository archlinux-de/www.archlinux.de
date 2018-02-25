<?php

namespace App\Tests\Command\Update;

use App\Entity\Packages\Package;
use App\Entity\Packages\Relations\AbstractRelation;
use App\Entity\Packages\Repository;
use App\Tests\Util\DatabaseTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \App\Command\Update\UpdatePackagesCommand
 */
class UpdatePackagesCommandTest extends DatabaseTestCase
{
    public function testCommand()
    {
        $application = $this->createApplication();

        $command = $application->find('app:update:packages');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertEquals(0, $commandTester->getStatusCode());
        $this->assertCount(1, $this->getRepository(Repository::class)->findAll());
        $this->assertGreaterThan(100, count($this->getRepository(Package::class)->findAll()));
        $this->assertGreaterThan(1000, count($this->getRepository(AbstractRelation::class)->findAll()));
    }
}
