<?php

namespace App\Command\Config;

use App\Entity\Country;
use App\Repository\CountryRepository;
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

    /** @var CountryRepository */
    private $countryRepository;

    /**
     * @param EntityManagerInterface $entityManager
     * @param CountryFetcher $countryFetcher
     * @param CountryRepository $countryRepository
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        CountryFetcher $countryFetcher,
        CountryRepository $countryRepository
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->countryFetcher = $countryFetcher;
        $this->countryRepository = $countryRepository;
    }

    protected function configure(): void
    {
        $this->setName('app:config:update-countries');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $codes = [];
        /** @var Country $country */
        foreach ($this->countryFetcher as $country) {
            $this->entityManager->merge($country);
            $codes[] = $country->getCode();
        }
        foreach ($this->countryRepository->findAllExceptByCodes($codes) as $country) {
            $this->entityManager->remove($country);
        }

        $this->entityManager->flush();

        return 0;
    }
}
