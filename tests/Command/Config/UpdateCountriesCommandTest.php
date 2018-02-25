<?php

namespace App\Tests\Command\Config;

use App\Command\Config\UpdateCountriesCommand;
use App\Entity\Country;
use App\Repository\CountryRepository;
use App\Service\CountryFetcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \App\Command\Config\UpdateCountriesCommand
 */
class UpdateCountriesCommandTest extends KernelTestCase
{
    public function testCommand()
    {
        $newCountry = new Country('DE');
        $oldCountry = new Country('DD');

        /** @var CountryRepository|\PHPUnit_Framework_MockObject_MockObject $countryRepository */
        $countryRepository = $this->createMock(CountryRepository::class);
        $countryRepository->method('findAllExceptByCodes')->willReturn([$oldCountry]);

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('merge')->with($newCountry);
        $entityManager->expects($this->once())->method('remove')->with($oldCountry);
        $entityManager->expects($this->once())->method('flush');

        /** @var CountryFetcher|\PHPUnit_Framework_MockObject_MockObject $countryFetcher */
        $countryFetcher = $this->createMock(CountryFetcher::class);
        $countryFetcher->method('fetchCountries')->willReturn([$newCountry]);

        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(new UpdateCountriesCommand($entityManager, $countryFetcher, $countryRepository));

        $command = $application->find('app:config:update-countries');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertEquals(0, $commandTester->getStatusCode());
    }
}
