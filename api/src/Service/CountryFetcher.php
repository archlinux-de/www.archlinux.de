<?php

namespace App\Service;

use App\Entity\Country;
use League\ISO3166\ISO3166;

/**
 * @implements \IteratorAggregate<Country>
 */
class CountryFetcher implements \IteratorAggregate
{
    public function __construct(private readonly ISO3166 $iso3166)
    {
    }

    public function getIterator(): \Traversable
    {
        foreach ($this->iso3166 as $iso3166Country) {
            assert(is_string($iso3166Country['alpha2']));
            assert(is_string($iso3166Country['name']));
            yield (new Country($iso3166Country['alpha2']))->setName($iso3166Country['name']);
        }
    }
}
