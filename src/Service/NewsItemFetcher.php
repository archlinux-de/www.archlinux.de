<?php

namespace App\Service;

use App\Entity\NewsAuthor;
use App\Entity\NewsItem;
use FeedIo\Feed\ItemInterface;
use FeedIo\FeedInterface;
use FeedIo\FeedIo;

/**
 * @phpstan-implements \IteratorAggregate<NewsItem>
 */
class NewsItemFetcher implements \IteratorAggregate
{
    /** @var FeedIo */
    private $feedIo;

    /** @var string */
    private $newsFeedUrl;

    /** @var NewsItemSlugger */
    private $slugger;

    /**
     * @param FeedIo $feedIo
     * @param string $newsFeedUrl
     * @param NewsItemSlugger $slugger
     */
    public function __construct(FeedIo $feedIo, string $newsFeedUrl, NewsItemSlugger $slugger)
    {
        $this->feedIo = $feedIo;
        $this->newsFeedUrl = $newsFeedUrl;
        $this->slugger = $slugger;
    }

    /**
     * @return \Traversable
     */
    public function getIterator(): \Traversable
    {
        /** @var ItemInterface $newsEntry */
        foreach ($this->fetchNewsFeed() as $newsEntry) {
            if (is_null($newsEntry->getPublicId())
                || is_null($newsEntry->getAuthor())
                || is_null($newsEntry->getTitle())
                || is_null($newsEntry->getLink())
                || is_null($newsEntry->getDescription())
                || is_null($newsEntry->getAuthor()->getName())
                || is_null($newsEntry->getLastModified())
            ) {
                throw new \RuntimeException('Invalid news entry');
            }
            $newsItem = new NewsItem($newsEntry->getPublicId());
            $newsItem
                ->setTitle($newsEntry->getTitle())
                ->setLink($newsEntry->getLink())
                ->setDescription($newsEntry->getDescription())
                ->setAuthor(
                    (new NewsAuthor())
                        ->setUri($newsEntry->getAuthor()->getUri())
                        ->setName($newsEntry->getAuthor()->getName())
                )
                ->setLastModified($newsEntry->getLastModified());
            $newsItem->setSlug($this->slugger->slugify($newsItem));
            yield $newsItem;
        }
    }

    /**
     * @return FeedInterface
     */
    private function fetchNewsFeed(): FeedInterface
    {
        $feed = $this->feedIo->read($this->newsFeedUrl)->getFeed();
        if ($feed->count() == 0) {
            throw new \RuntimeException('empty news feed');
        }
        return $feed;
    }
}
