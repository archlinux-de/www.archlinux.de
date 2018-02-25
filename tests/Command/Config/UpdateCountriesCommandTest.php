<?php

namespace App\Tests\Command\Config;

use App\Entity\Country;
use App\Tests\Util\DatabaseTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \App\Command\Config\UpdateCountriesCommand
 */
class UpdateCountriesCommandTest extends DatabaseTestCase
{
    public function testCommand()
    {
        $application = $this->createApplication();

        $command = $application->find('app:config:update-countries');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertEquals(0, $commandTester->getStatusCode());
        $this->assertGreaterThan(200, count($this->getRepository(Country::class)->findAll()));
    }
}
