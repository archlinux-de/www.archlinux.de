<?php

namespace App\ArchLinux;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PackageDatabaseMirror
{
    /** @var int */
    private $lastMirrorUpdate = 0;

    /** @var HttpClientInterface */
    private $httpClient;

    /** @var CacheItemPoolInterface */
    private $cache;

    /** @var string */
    private $mirrorUrl;

    /**
     * @param HttpClientInterface $httpClient
     * @param CacheItemPoolInterface $cache
     * @param string $mirrorUrl
     */
    public function __construct(HttpClientInterface $httpClient, CacheItemPoolInterface $cache, string $mirrorUrl)
    {
        $this->httpClient = $httpClient;
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
            $content = $this->httpClient->request(
                'GET',
                $this->mirrorUrl . 'lastupdate'
            )->getContent();
            $this->lastMirrorUpdate = (int)$content;

            return $this->lastMirrorUpdate !== (int)$lastLocalUpdateCache->get();
        } else {
            return true;
        }
    }

    public function updateLastUpdate(): void
    {
        $lastLocalUpdateCache = $this->cache->getItem('UpdatePackages-lastupdate')->set($this->lastMirrorUpdate);
        $this->cache->save($lastLocalUpdateCache);
    }
}
