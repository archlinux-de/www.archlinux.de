<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class PackageDatabaseDownloader
{
    private const DB_EXT = '.files';

    public function __construct(
        private HttpClientInterface $httpClient,
        private PackageDatabaseMirror $packageDatabaseMirror
    ) {
    }

    public function download(string $repository, string $architecture): string
    {
        $url = $this->packageDatabaseMirror->getMirrorUrl()
            . $repository . '/os/' . $architecture . '/' . $repository . self::DB_EXT;

        $response = $this->httpClient->request('GET', $url);

        return $response->getContent();
    }
}
