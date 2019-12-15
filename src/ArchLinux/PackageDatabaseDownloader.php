<?php

namespace App\ArchLinux;

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
     * @return \SplFileObject
     */
    public function download(string $repository, string $architecture): \SplFileObject
    {
        $url = $this->packageDatabaseMirror->getMirrorUrl()
            . $repository . '/os/' . $architecture . '/' . $repository . self::DB_EXT;

        $tmpFilePrefix = strtolower((string)preg_replace('/\W+/', '_', $url));
        $tmpFile = new TemporaryFile($tmpFilePrefix);

        $response = $this->httpClient->request('GET', $url);
        foreach ($this->httpClient->stream($response) as $chunk) {
            $tmpFile->fwrite($chunk->getContent());
        }
        $tmpFile->fflush();

        $mtime = strtotime($response->getHeaders()['last-modified'][0]);
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
