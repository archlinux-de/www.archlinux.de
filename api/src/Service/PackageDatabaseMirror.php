<?php

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PackageDatabaseMirror
{
    /** @var string */
    public const CACHE_KEY = 'PackageDatabaseMirror_lastupdate';

    /** @var string */
    private $lastUpdateHash = '';

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
        $lastLocalUpdateCache = $this->cache->getItem(self::CACHE_KEY);
        if ($lastLocalUpdateCache->isHit()) {
            $contentHash = (string)hash(
                'sha256',
                $this->httpClient->request(
                    'GET',
                    $this->mirrorUrl . 'lastupdate'
                )->getContent()
            );
            $this->lastUpdateHash = $contentHash;
            return $this->lastUpdateHash !== (string)$lastLocalUpdateCache->get();
        }

        return true;
    }

    public function updateLastUpdate(): void
    {
        $lastLocalUpdateCache = $this->cache->getItem(self::CACHE_KEY)->set($this->lastUpdateHash);
        $this->cache->save($lastLocalUpdateCache);
    }
}
