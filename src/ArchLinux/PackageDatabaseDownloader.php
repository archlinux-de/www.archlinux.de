<?php

namespace App\ArchLinux;

use GuzzleHttp\ClientInterface;

class PackageDatabaseDownloader
{
    private const DB_EXT = '.files';

    /** @var ClientInterface */
    private $guzzleClient;

    /** @var PackageDatabaseMirror */
    private $packageDatabaseMirror;

    /**
     * @param ClientInterface $guzzleClient
     * @param PackageDatabaseMirror $packageDatabaseMirror
     */
    public function __construct(ClientInterface $guzzleClient, PackageDatabaseMirror $packageDatabaseMirror)
    {
        $this->guzzleClient = $guzzleClient;
        $this->packageDatabaseMirror = $packageDatabaseMirror;
    }

    /**
     * @param string $repository
     * @param string $architecture
     * @return \SplFileObject
     */
    public function download(string $repository, string $architecture): \SplFileObject
    {
        $url = $this->packageDatabaseMirror->getMirrorUrl()
            . $repository . '/os/' . $architecture . '/' . $repository . self::DB_EXT;

        $tmpFilePrefix = strtolower((string)preg_replace('/\W+/', '_', $url));
        $tmpFile = new TemporaryFile($tmpFilePrefix);

        $response = $this->guzzleClient->request('GET', $url, ['sink' => $tmpFile->getRealPath()]);
        $mtime = strtotime($response->getHeaderLine('Last-Modified'));
        $tmpFile->setMTime((int)$mtime);

        return $tmpFile;
    }

    /**
     * @param \SplFileInfo $packageDatabaseFile
     * @return \IteratorAggregate
     */
    public function createDatabase(\SplFileInfo $packageDatabaseFile): \IteratorAggregate
    {
        return new PackageDatabase(new PackageDatabaseReader($packageDatabaseFile));
    }
}
