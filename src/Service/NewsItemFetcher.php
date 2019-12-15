<?php

namespace App\Service;

use App\Entity\NewsAuthor;
use App\Entity\NewsItem;
use GuzzleHttp\ClientInterface;

/**
 * @phpstan-implements \IteratorAggregate<NewsItem>
 */
class NewsItemFetcher implements \IteratorAggregate
{
    /** @var string */
    private $newsFeedUrl;

    /** @var NewsItemSlugger */
    private $slugger;

    /** @var ClientInterface */
    private $guzzleClient;

    /**
     * @param string $newsFeedUrl
     * @param NewsItemSlugger $slugger
     * @param ClientInterface $guzzleClient
     */
    public function __construct(string $newsFeedUrl, NewsItemSlugger $slugger, ClientInterface $guzzleClient)
    {
        $this->newsFeedUrl = $newsFeedUrl;
        $this->slugger = $slugger;
        $this->guzzleClient = $guzzleClient;
    }

    /**
     * @return \Traversable
     */
    public function getIterator(): \Traversable
    {
        foreach ($this->fetchNewsFeed()->entry as $newsEntry) {
            if (is_null($newsEntry->id)
                || is_null($newsEntry->title)
                || is_null($newsEntry->link)
                || is_null($newsEntry->link->attributes())
                || is_null($newsEntry->summary)
                || is_null($newsEntry->author)
                || is_null($newsEntry->author->name)
                || is_null($newsEntry->author->uri)
                || is_null($newsEntry->updated)
            ) {
                throw new \RuntimeException('Invalid news entry');
            }
            $newsItem = new NewsItem((string)$newsEntry->id);
            $newsItem
                ->setTitle((string)$newsEntry->title)
                ->setLink((string)$newsEntry->link->attributes()->href)
                ->setDescription((string)$newsEntry->summary)
                ->setAuthor(
                    (new NewsAuthor())
                        ->setUri((string)$newsEntry->author->uri)
                        ->setName((string)$newsEntry->author->name)
                )
                ->setLastModified(new \DateTime((string)$newsEntry->updated));
            $newsItem->setSlug($this->slugger->slugify($newsItem));
            yield $newsItem;
        }
    }

    /**
     * @return \SimpleXMLElement
     */
    private function fetchNewsFeed(): \SimpleXMLElement
    {
        $response = $this->guzzleClient->request('GET', $this->newsFeedUrl);
        $content = $response->getBody()->getContents();

        libxml_use_internal_errors(true);
        $feed = simplexml_load_string($content);

        if (!$feed) {
            $error = libxml_get_last_error();
            if ($error) {
                throw new \RuntimeException($error->message, $error->code);
            } else {
                throw new \RuntimeException('empty news feed');
            }
        }

        return $feed;
    }
}
