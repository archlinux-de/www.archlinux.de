<?php

namespace App\Command\Config;

use App\Entity\Country;
use App\Repository\CountryRepository;
use App\Service\CountryFetcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UpdateCountriesCommand extends Command
{
    use LockableTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CountryFetcher $countryFetcher,
        private readonly CountryRepository $countryRepository,
        private readonly ValidatorInterface $validator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('app:config:update-countries');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->lock('countries.lock');

        $codes = [];
        /** @var Country $country */
        foreach ($this->countryFetcher as $country) {
            $errors = $this->validator->validate($country);
            if ($errors->count() > 0) {
                throw new ValidationFailedException($country, $errors);
            }

            /** @var Country|null $persistedCountry */
            $persistedCountry = $this->countryRepository->find($country->getCode());
            if ($persistedCountry) {
                $country = $persistedCountry->update($country);
            } else {
                $this->entityManager->persist($country);
            }

            $codes[] = $country->getCode();
        }
        foreach ($this->countryRepository->findAllExceptByCodes($codes) as $country) {
            $this->entityManager->remove($country);
        }

        $this->entityManager->flush();
        $this->release();

        return Command::SUCCESS;
    }
}
