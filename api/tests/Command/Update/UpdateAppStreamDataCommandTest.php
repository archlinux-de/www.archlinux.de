<?php

namespace App\Tests\Command\Update;

use App\Command\Update\UpdateAppStreamDataCommand;
use App\Dto\AppStreamDataComponentDto;
use App\Entity\Packages\Package;
use App\Repository\PackageRepository;
use App\Service\AppStreamDataFetcher;
use App\Service\KeywordProcessor;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class UpdateAppStreamDataCommandTest extends KernelTestCase
{
    public function testCommand(): void
    {
        $package = $this->createStub(Package::class);
        $package->method('getName')->willReturn('abisource');

        $packageRepository = $this->createStub(PackageRepository::class);
        $packageRepository->method('findOneByName')->willReturn($package);

        $appStreamDataComponentDto = $this->createStub(AppStreamDataComponentDto::class);
        $appStreamDataComponentDto->method('getPackageName')->willReturn('abisource');

        $keywordProcessor = $this->createStub(KeywordProcessor::class);

        $logger = $this->createStub(LoggerInterface::class);

        $appStreamDataFetcher = $this->createStub(AppStreamDataFetcher::class);
        $appStreamDataFetcher->method('getIterator')->willReturn(new \ArrayIterator([$appStreamDataComponentDto]));

        /** @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist')->with($package);
        $entityManager->expects($this->once())->method('flush');


        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application
            ->addCommand(
                new UpdateAppStreamDataCommand(
                    $appStreamDataFetcher,
                    $entityManager,
                    $packageRepository,
                    $keywordProcessor,
                    $logger
                )
            );

        $command = $application->find('app:update:appstream-data');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertEquals(0, $commandTester->getStatusCode());
    }
}
