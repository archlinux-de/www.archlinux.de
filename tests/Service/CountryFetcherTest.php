<?php

namespace App\Tests\Service;

use App\Service\CountryFetcher;
use League\ISO3166\ISO3166;
use PHPUnit\Framework\TestCase;

class CountryFetcherTest extends TestCase
{
    public function testFetchCountries()
    {
        $countryFetcher = $this->createCountryFetcher([
            ['alpha2' => 'DE', 'name' => 'Germany']
        ]);
        $countries = $countryFetcher->fetchCountries();

        $this->assertCount(1, $countries);
        $this->assertEquals('DE', $countries[0]->getCode());
        $this->assertEquals('Germany', $countries[0]->getName());
    }

    /**
     * @param array $countries
     * @return CountryFetcher
     */
    private function createCountryFetcher(array $countries): CountryFetcher
    {
        return new CountryFetcher(new ISO3166($countries));
    }

    public function testFetchCountryCodes()
    {
        $countryFetcher = $this->createCountryFetcher([
            ['alpha2' => 'DE']
        ]);
        $countryCodes = $countryFetcher->fetchCountryCodes();

        $this->assertEquals(['DE'], $countryCodes);
    }

    public function testIso3166Interface()
    {
        foreach (new ISO3166() as $country) {
            $this->assertArrayHasKey('alpha2', $country);
            $this->assertNotEmpty($country['alpha2']);
            $this->assertArrayHasKey('name', $country);
        }
    }
}
