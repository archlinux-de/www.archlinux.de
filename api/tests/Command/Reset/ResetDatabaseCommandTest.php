<?php

namespace App\Tests\Command\Reset;

use App\Command\Reset\ResetDatabaseCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpKernel\KernelInterface;
use SymfonyDatabaseTest\DatabaseTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(ResetDatabaseCommand::class)]
class ResetDatabaseCommandTest extends DatabaseTestCase
{
    public function testCommand(): void
    {
        $this->assertInstanceOf(KernelInterface::class, static::$kernel);
        $command = new Application(static::$kernel)->find('app:reset:database');
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
