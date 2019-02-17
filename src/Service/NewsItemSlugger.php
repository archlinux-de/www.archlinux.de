<?php

namespace App\Service;

use App\Entity\NewsItem;

class NewsItemSlugger
{
    /** @var Slugger */
    private $slugger;

    /**
     * @param Slugger $slugger
     */
    public function __construct(Slugger $slugger)
    {
        $this->slugger = $slugger;
    }

    /**
     * @param NewsItem $newsItem
     * @return string
     */
    public function slugify(NewsItem $newsItem): string
    {
        return substr(
            $this->parseId($newsItem->getId()) . '-' . $this->slugger->slugify($newsItem->getTitle()),
            0,
            255
        );
    }

    /**
     * @param string $id
     * @return int
     */
    private function parseId(string $id): int
    {
        if (preg_match('/id=(\d+)/', $id, $matches)) {
            return (int)$matches[1];
        } elseif (preg_match('/^\d+$/', $id)) {
            return (int)$id;
        }
        return crc32($id);
    }
}
