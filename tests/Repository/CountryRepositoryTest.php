<?php

namespace App\Tests\Repository;

use App\Entity\Country;
use App\Repository\CountryRepository;
use SymfonyDatabaseTest\DatabaseTestCase;

class CountryRepositoryTest extends DatabaseTestCase
{
    public function testFindAllExceptByIds(): void
    {
        $countryA = (new Country('A'))->setName('');
        $countryB = (new Country('B'))->setName('');
        $entityManager = $this->getEntityManager();
        $entityManager->persist($countryA);
        $entityManager->persist($countryB);
        $entityManager->flush();

        /** @var CountryRepository $countryRepository */
        $countryRepository = $this->getRepository(Country::class);
        $countries = $countryRepository->findAllExceptByCodes(['A']);

        $this->assertCount(1, $countries);
        $this->assertEquals('B', $countries[0]->getCode());
    }
}
