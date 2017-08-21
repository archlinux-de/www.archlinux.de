<?php

namespace archportal\lib;

use GuzzleHttp\Client;

class Download
{
    /** @var bool */
    private $downloaded = false;
    /** @var string */
    private $url = '';
    /** @var string */
    private $tmpFile = '';
    /** @var int */
    private $mtime = 0;
    /** @var Client */
    private $guzzleClient;

    /**
     * @param Client $guzzleClient
     * @param string $url
     */
    public function __construct(Client $guzzleClient, string $url)
    {
        $this->url = $url;
        $this->guzzleClient = $guzzleClient;
        $this->tmpFile = tempnam(sys_get_temp_dir(), strtolower(str_replace('\\', '/', get_class($this))));
    }

    public function __destruct()
    {
        if (file_exists($this->tmpFile)) {
            unlink($this->tmpFile);
        }
    }

    /**
     * @return int
     */
    public function getMTime(): int
    {
        if (!$this->downloaded && $this->mtime == 0) {
            $response = $this->guzzleClient->request('HEAD', $this->url);
            $mtime = strtotime($response->getHeaderLine('Last-Modified'));

            if ($mtime < 1) {
                throw new \RuntimeException('Invalid filetime "' . $mtime . '" for "' . $this->url . '"');
            } else {
                $this->mtime = $mtime;
            }
        }

        return $this->mtime;
    }

    /**
     * @return string
     */
    public function getFile(): string
    {
        if (!$this->downloaded) {
            $fh = fopen($this->tmpFile, 'w');
            flock($fh, \LOCK_EX);

            $response = $this->guzzleClient->request('GET', $this->url, ['sink' => $fh]);
            $this->mtime = strtotime($response->getHeaderLine('Last-Modified'));

            flock($fh, \LOCK_UN);
            fclose($fh);

            $this->downloaded = true;
        }

        return $this->tmpFile;
    }
}
