<?php

namespace App\Service;

use App\Entity\NewsAuthor;
use App\Entity\NewsItem;
use FeedIo\Feed\ItemInterface;
use FeedIo\FeedInterface;
use FeedIo\FeedIo;

class NewsItemFetcher
{
    /** @var FeedIo */
    private $feedIo;

    /** @var string */
    private $newsFeedUrl;

    /**
     * @param FeedIo $feedIo
     * @param string $newsFeedUrl
     */
    public function __construct(FeedIo $feedIo, string $newsFeedUrl)
    {
        $this->feedIo = $feedIo;
        $this->newsFeedUrl = $newsFeedUrl;
    }

    /**
     * @return NewsItem[]
     */
    public function fetchNewsItems(): array
    {
        return iterator_to_array((function () {
            /** @var ItemInterface $newsEntry */
            foreach ($this->fetchNewsFeed() as $newsEntry) {
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
                yield $newsItem;
            }
        })());
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
