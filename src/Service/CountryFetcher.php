<?php

namespace App\Service;

use App\Entity\Country;
use League\ISO3166\ISO3166;

/**
 * @phpstan-implements \IteratorAggregate<Country>
 */
class CountryFetcher implements \IteratorAggregate
{
    /** @var ISO3166<array<string>> */
    private $iso3166;

    /**
     * @param ISO3166<array<string>> $iso3166
     */
    public function __construct(ISO3166 $iso3166)
    {
        $this->iso3166 = $iso3166;
    }

    /**
     * @return \Traversable<Country>
     */
    public function getIterator(): \Traversable
    {
        foreach ($this->iso3166 as $iso3166Country) {
            yield (new Country($iso3166Country['alpha2']))->setName($iso3166Country['name']);
        }
    }
}
