<?php

namespace App\Tests\Command\Config;

use App\Command\Config\UpdateCountriesCommand;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class UpdateCountriesCommandTest extends KernelTestCase
{
    public function testCommand()
    {
        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(new UpdateCountriesCommand($entityManager));

        $command = $application->find('app:config:update-countries');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $output = $commandTester->getDisplay();
        $this->assertEmpty($output);
    }
}
