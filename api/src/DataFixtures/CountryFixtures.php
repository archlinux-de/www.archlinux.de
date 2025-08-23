<?php

namespace App\DataFixtures;

use App\Entity\Country;
use App\Service\CountryFetcher;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CountryFixtures extends Fixture
{
    public function __construct(
        private readonly CountryFetcher $countryFetcher,
        private readonly ValidatorInterface $validator
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        /** @var Country $country */
        foreach ($this->countryFetcher as $country) {
            $errors = $this->validator->validate($country);
            if ($errors->count() > 0) {
                throw new \RuntimeException((string) $errors);
            }

            $manager->persist($country);
            $this->addReference($country->getCode(), $country);
        }

        $manager->flush();
    }
}
