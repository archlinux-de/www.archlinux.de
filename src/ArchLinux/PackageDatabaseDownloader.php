<?php

namespace App\ArchLinux;

use GuzzleHttp\Client;

class PackageDatabaseDownloader
{
    private const DB_EXT = '.db';

    /** @var Client */
    private $guzzleClient;

    /**
     * @param Client $guzzleClient
     */
    public function __construct(Client $guzzleClient)
    {
        $this->guzzleClient = $guzzleClient;
    }

    /**
     * @param string $mirror
     * @param string $repository
     * @param string $architecture
     * @return \SplFileObject
     */
    public function download(string $mirror, string $repository, string $architecture): \SplFileObject
    {
        $url = $mirror . $repository . '/os/' . $architecture . '/' . $repository . self::DB_EXT;

        $tmpFilePrefix = strtolower(preg_replace('/\W+/', '_', $url));
        $tmpFile = new TemporaryFile($tmpFilePrefix);

        $response = $this->guzzleClient->request('GET', $url, ['sink' => $tmpFile->getRealPath()]);
        $mtime = strtotime($response->getHeaderLine('Last-Modified'));
        $tmpFile->setMTime($mtime);

        return $tmpFile;
    }
}
