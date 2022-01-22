<?php

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PackageDatabaseMirror
{
    /** @var string */
    public const CACHE_KEY = 'PackageDatabaseMirror_lastupdate';

    /** @var string */
    private string $lastUpdateHash = '';

    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheItemPoolInterface $cache,
        private string $mirrorUrl
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
            $contentHash = (string)hash(
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
