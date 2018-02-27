<?php

namespace App\Service;

use App\Entity\Country;
use League\ISO3166\ISO3166;

class CountryFetcher implements \IteratorAggregate
{
    /** @var ISO3166 */
    private $iso3166;

    /**
     * @param ISO3166 $iso3166
     */
    public function __construct(ISO3166 $iso3166)
    {
        $this->iso3166 = $iso3166;
    }

    /**
     * @return iterable
     */
    public function getIterator(): iterable
    {
        foreach ($this->iso3166 as $iso3166Country) {
            yield (new Country($iso3166Country['alpha2']))->setName($iso3166Country['name']);
        }
    }
}
