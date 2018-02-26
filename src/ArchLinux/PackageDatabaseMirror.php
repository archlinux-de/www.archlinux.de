<?php

namespace App\ArchLinux;

use GuzzleHttp\Client;
use Psr\Cache\CacheItemPoolInterface;

class PackageDatabaseMirror
{
    /** @var int */
    private $lastMirrorUpdate = 0;

    /** @var Client */
    private $guzzleClient;

    /** @var CacheItemPoolInterface */
    private $cache;

    /** @var string */
    private $mirrorUrl;

    /**
     * @param Client $guzzleClient
     * @param CacheItemPoolInterface $cache
     * @param string $mirrorUrl
     */
    public function __construct(Client $guzzleClient, CacheItemPoolInterface $cache, string $mirrorUrl)
    {
        $this->guzzleClient = $guzzleClient;
        $this->cache = $cache;
        $this->mirrorUrl = $mirrorUrl;
    }

    /**
     * @return string
     */
    public function getMirrorUrl(): string
    {
        return $this->mirrorUrl;
    }

    /**
     * @return bool
     */
    public function hasUpdated(): bool
    {
        $lastLocalUpdateCache = $this->cache->getItem('UpdatePackages-lastupdate');
        if ($lastLocalUpdateCache->isHit()) {
            $content = $this->guzzleClient->request(
                'GET',
                $this->mirrorUrl . 'lastupdate'
            )->getBody()->getContents();
            $this->lastMirrorUpdate = (int)$content;

            return $this->lastMirrorUpdate !== (int)$lastLocalUpdateCache->get();
        } else {
            return true;
        }
    }

    public function updateLastUpdate()
    {
        $lastLocalUpdateCache = $this->cache->getItem('UpdatePackages-lastupdate')->set($this->lastMirrorUpdate);
        $this->cache->save($lastLocalUpdateCache);
    }
}
