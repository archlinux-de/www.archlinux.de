<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class PackageDatabaseDownloader
{
    private const DB_EXT = '.files';

    /** @var HttpClientInterface */
    private $httpClient;

    /** @var PackageDatabaseMirror */
    private $packageDatabaseMirror;

    /**
     * @param HttpClientInterface $httpClient
     * @param PackageDatabaseMirror $packageDatabaseMirror
     */
    public function __construct(HttpClientInterface $httpClient, PackageDatabaseMirror $packageDatabaseMirror)
    {
        $this->httpClient = $httpClient;
        $this->packageDatabaseMirror = $packageDatabaseMirror;
    }

    /**
     * @param string $repository
     * @param string $architecture
     * @return string
     */
    public function download(string $repository, string $architecture): string
    {
        $url = $this->packageDatabaseMirror->getMirrorUrl()
            . $repository . '/os/' . $architecture . '/' . $repository . self::DB_EXT;

        $response = $this->httpClient->request('GET', $url);

        return $response->getContent();
    }
}
