<?php

namespace App\Tests\Service;

use App\Entity\Country;
use App\Service\CountryFetcher;
use League\ISO3166\ISO3166;
use PHPUnit\Framework\TestCase;

class CountryFetcherTest extends TestCase
{
    public function testFetchCountries(): void
    {
        $countryFetcher = new CountryFetcher(
            new ISO3166(
                [
                    ['alpha2' => 'DE', 'name' => 'Germany']
                ]
            )
        );
        /** @var Country[] $countries */
        $countries = iterator_to_array($countryFetcher);

        $this->assertCount(1, $countries);
        $this->assertEquals('DE', $countries[0]->getCode());
        $this->assertEquals('Germany', $countries[0]->getName());
    }
}
