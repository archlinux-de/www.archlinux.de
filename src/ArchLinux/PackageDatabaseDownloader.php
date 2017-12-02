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
        $tmpFile = $this->createTemporaryFile($tmpFilePrefix);

        $response = $this->guzzleClient->request('GET', $url, ['sink' => $tmpFile->getRealPath()]);
        $mtime = strtotime($response->getHeaderLine('Last-Modified'));
        $tmpFile->setMTime($mtime);

        return $tmpFile;
    }

    /**
     * Temporary file which will be removed by the garbage collector
     * @param string $prefix
     * @return \SplFileObject
     */
    private function createTemporaryFile(string $prefix): \SplFileObject
    {
        return new class($prefix) extends \SplFileObject
        {
            /** @var string */
            private $fileName;
            /** @var int */
            private $mTime;

            /**
             * @param string $prefix
             */
            public function __construct(string $prefix)
            {
                $this->fileName = tempnam(sys_get_temp_dir(), $prefix);
                parent::__construct($this->fileName);
                $this->mTime = parent::getMTime();
            }

            public function __destruct()
            {
                if (is_writable($this->fileName)) {
                    unlink($this->fileName);
                }
            }

            /**
             * @return int
             */
            public function getMTime(): int
            {
                return $this->mTime;
            }

            /**
             * @param int $mtime
             */
            public function setMTime(int $mtime)
            {
                touch($this->getRealPath(), $mtime);
                $this->mTime = $mtime;
            }
        };
    }
}
