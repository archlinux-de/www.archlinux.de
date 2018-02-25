<?php

namespace App\Command\Config;

use App\Entity\Country;
use Doctrine\ORM\EntityManagerInterface;
use League\ISO3166\ISO3166;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCountriesCommand extends Command
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
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
        foreach (new ISO3166() as $iso3166Country) {
            $country = (new Country($iso3166Country['alpha2']))->setName($iso3166Country['name']);
            $this->entityManager->merge($country);
        }

        $countryRepository = $this->entityManager->getRepository(Country::class);
        $countryIds = array_keys(iterator_to_array((new ISO3166())->iterator()));
        foreach ($countryRepository->findAllExceptByIds($countryIds) as $country) {
            $this->entityManager->remove($country);
        }

        $this->entityManager->flush();
    }
}
