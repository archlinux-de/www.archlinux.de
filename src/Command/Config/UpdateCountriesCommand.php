<?php

namespace App\Command\Config;

use App\Entity\Country;
use App\Service\CountryFetcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCountriesCommand extends Command
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var CountryFetcher */
    private $countryFetcher;

    /**
     * @param EntityManagerInterface $entityManager
     * @param CountryFetcher $countryFetcher
     */
    public function __construct(EntityManagerInterface $entityManager, CountryFetcher $countryFetcher)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->countryFetcher = $countryFetcher;
    }

    protected function configure()
    {
        $this->setName('app:config:update-countries');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->countryFetcher->fetchCountries() as $country) {
            $this->entityManager->merge($country);
        }

        $countryRepository = $this->entityManager->getRepository(Country::class);
        $countryIds = $this->countryFetcher->fetchCountryCodes();
        foreach ($countryRepository->findAllExceptByIds($countryIds) as $country) {
            $this->entityManager->remove($country);
        }

        $this->entityManager->flush();
    }
}
