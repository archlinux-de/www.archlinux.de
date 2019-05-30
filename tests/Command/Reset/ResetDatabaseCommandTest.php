<?php

namespace App\Tests\Command\Reset;

use App\Tests\Util\DatabaseTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \App\Command\Reset\ResetDatabaseCommand
 */
class ResetDatabaseCommandTest extends DatabaseTestCase
{
    public function testCommand()
    {
        $command = (new Application(static::$kernel))->find('app:reset:database');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
                '--packages' => 1,
                '--countries' => 1,
                '--mirrors' => 1,
                '--releases' => 1,
                '--news' => 1
            ]
        );

        $this->assertEquals(0, $commandTester->getStatusCode());
    }
}
