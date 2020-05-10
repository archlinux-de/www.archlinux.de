<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use SymfonyDatabaseTest\DatabaseTestCase;

abstract class DatabaseSearchTestCase extends DatabaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $application = new Application(static::getClient()->getKernel());
        $application->setAutoExit(false);
        $input = new ArrayInput(['command' => 'fos:elastica:reset', '--quiet' => true]);
        $output = new BufferedOutput();
        $result = $application->run($input, $output);
        $outputResult = $output->fetch();

        static::assertEmpty($outputResult, $outputResult);
        static::assertEquals(0, $result, sprintf('Command %s failed', $input));
    }
}
