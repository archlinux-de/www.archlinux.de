<?php

namespace App\Tests\Service;

use App\Service\CountryFetcher;
use League\ISO3166\ISO3166;
use PHPUnit\Framework\TestCase;

class CountryFetcherTest extends TestCase
{
    public function testFetchCountries()
    {
        $countryFetcher = new CountryFetcher(
            new ISO3166(
                [
                    ['alpha2' => 'DE', 'name' => 'Germany']
                ]
            )
        );
        $countries = $countryFetcher->fetchCountries();

        $this->assertCount(1, $countries);
        $this->assertEquals('DE', $countries[0]->getCode());
        $this->assertEquals('Germany', $countries[0]->getName());
    }
}
