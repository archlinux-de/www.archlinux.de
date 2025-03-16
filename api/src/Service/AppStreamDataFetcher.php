<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AppStreamDataFetcher implements \IteratorAggregate
{
    public function __construct(private string $appStreamDataBaseUrl, private HttpClientInterface $httpClient)
    {
    }

    public function getIterator(): \Traversable
    {
        // TODO: fetch upstream xml.gz (baseURL, get package version, get only repos we need (core, extra)
        // extract xml
        // use serializer to extract xml
        // read values we want to have at MetaData entity
        // yield entity
        // data to extract: component type, name & description in German, categories

    }
}
