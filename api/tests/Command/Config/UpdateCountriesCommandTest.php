<?php

namespace App\Tests\Command\Config;

use App\Command\Config\UpdateCountriesCommand;
use App\Entity\Country;
use App\Repository\CountryRepository;
use App\Service\CountryFetcher;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[CoversClass(UpdateCountriesCommand::class)]
class UpdateCountriesCommandTest extends KernelTestCase
{
    public function testCommand(): void
    {
        $newCountry = new Country('DE');
        $oldCountry = new Country('DD');

        /** @var CountryRepository&MockObject $countryRepository */
        $countryRepository = $this->createMock(CountryRepository::class);
        $countryRepository->method('findAllExceptByCodes')->willReturn([$oldCountry]);

        /** @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist')->with($newCountry);
        $entityManager->expects($this->once())->method('remove')->with($oldCountry);
        $entityManager->expects($this->once())->method('flush');

        /** @var CountryFetcher&MockObject $countryFetcher */
        $countryFetcher = $this->createMock(CountryFetcher::class);
        $countryFetcher->method('getIterator')->willReturn(new \ArrayIterator([$newCountry]));

        /** @var ValidatorInterface&MockObject $validator */
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->atLeastOnce())->method('validate')->willReturn(new ConstraintViolationList());

        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->addCommand(
            new UpdateCountriesCommand($entityManager, $countryFetcher, $countryRepository, $validator)
        );

        $command = $application->find('app:config:update-countries');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testFailOnInvalidCountries(): void
    {
        /** @var CountryRepository&MockObject $countryRepository */
        $countryRepository = $this->createMock(CountryRepository::class);
        $countryRepository->expects($this->never())->method('findAllExceptByCodes');

        /** @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');

        /** @var CountryFetcher&MockObject $countryFetcher */
        $countryFetcher = $this->createMock(CountryFetcher::class);
        $countryFetcher->method('getIterator')->willReturn(new \ArrayIterator([new Country('%invalid')]));

        /** @var ValidatorInterface&MockObject $validator */
        $validator = $this->createMock(ValidatorInterface::class);
        $validator
            ->expects($this->atLeastOnce())
            ->method('validate')
            ->willReturn(new ConstraintViolationList([$this->createMock(ConstraintViolation::class)]));

        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->addCommand(
            new UpdateCountriesCommand($entityManager, $countryFetcher, $countryRepository, $validator)
        );

        $command = $application->find('app:config:update-countries');
        $commandTester = new CommandTester($command);
        $this->expectException(ValidationFailedException::class);
        $commandTester->execute(['command' => $command->getName()]);
    }

    public function testUpdateCountry(): void
    {
        $country = new Country('DE')->setName('Germany');

        /** @var CountryRepository&MockObject $countryRepository */
        $countryRepository = $this->createMock(CountryRepository::class);
        $countryRepository->expects($this->once())->method('find')->with($country->getCode())->willReturn($country);

        /** @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        /** @var CountryFetcher&MockObject $countryFetcher */
        $countryFetcher = $this->createMock(CountryFetcher::class);
        $countryFetcher->method('getIterator')->willReturn(new \ArrayIterator([$country]));

        /** @var ValidatorInterface&MockObject $validator */
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->atLeastOnce())->method('validate')->willReturn(new ConstraintViolationList());

        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->addCommand(
            new UpdateCountriesCommand($entityManager, $countryFetcher, $countryRepository, $validator)
        );

        $command = $application->find('app:config:update-countries');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertEquals(0, $commandTester->getStatusCode());
    }
}
