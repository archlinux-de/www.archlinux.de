<?php

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PackageDatabaseMirror
{
    public const string CACHE_KEY = 'PackageDatabaseMirror_lastupdate';

    private string $lastUpdateHash = '';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheItemPoolInterface $cache,
        private readonly string $mirrorUrl
    ) {
    }

    public function getMirrorUrl(): string
    {
        return $this->mirrorUrl;
    }

    public function hasUpdated(): bool
    {
        $lastLocalUpdateCache = $this->cache->getItem(self::CACHE_KEY);
        if ($lastLocalUpdateCache->isHit()) {
            $contentHash = hash(
                'sha256',
                $this->httpClient->request(
                    'GET',
                    $this->mirrorUrl . 'lastupdate'
                )->getContent()
            );
            $this->lastUpdateHash = $contentHash;
            $cachedLastUpdateHash = $lastLocalUpdateCache->get();
            return !is_string($cachedLastUpdateHash) || $this->lastUpdateHash !== $cachedLastUpdateHash;
        }

        return true;
    }

    public function updateLastUpdate(): void
    {
        $lastLocalUpdateCache = $this->cache->getItem(self::CACHE_KEY)->set($this->lastUpdateHash);
        $this->cache->save($lastLocalUpdateCache);
    }
}
