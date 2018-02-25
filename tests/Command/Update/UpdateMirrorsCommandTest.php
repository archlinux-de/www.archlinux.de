<?php

namespace App\Tests\Command\Update;

use App\Command\Update\UpdateMirrorsCommand;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \App\Command\Update\UpdateMirrorsCommand
 */
class UpdateMirrorsCommandTest extends KernelTestCase
{
    public function testCommand()
    {
        /** @var ObjectRepository|\PHPUnit_Framework_MockObject_MockObject $mirrorRepository */
        $mirrorRepository = $this->createMock(ObjectRepository::class);
        $mirrorRepository->method('findAll')->willReturn([]);

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($mirrorRepository);

        /** @var StreamInterface|\PHPUnit_Framework_MockObject_MockObject $RequestBody */
        $responseBody = $this->createMock(StreamInterface::class);
        $responseBody
            ->method('getContents')
            ->willReturn(
                json_encode(
                    [
                        'version' => 3,
                        'urls' => [
                            [
                                'url' => '',
                                'protocol' => '',
                                'country_code' => null,
                                'last_sync' => null,
                                'delay' => 1,
                                'duration_avg' => 1,
                                'score' => 1,
                                'completion_pct' => 1,
                                'duration_stddev' => 1
                            ]
                        ]
                    ]
                )
            );

        /** @var ResponseInterface|\PHPUnit_Framework_MockObject_MockObject $response */
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($responseBody);

        /** @var Client|\PHPUnit_Framework_MockObject_MockObject $guzzleClient */
        $guzzleClient = $this->createMock(Client::class);
        $guzzleClient->method('request')->willReturn($response);

        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(new UpdateMirrorsCommand($entityManager, $guzzleClient, 'http://localhost/'));

        $command = $application->find('app:update:mirrors');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $output = $commandTester->getDisplay();
        $this->assertEmpty($output);
    }
}
