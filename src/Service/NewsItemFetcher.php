<?php

namespace App\Service;

use App\Entity\NewsAuthor;
use App\Entity\NewsItem;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @phpstan-implements \IteratorAggregate<NewsItem>
 */
class NewsItemFetcher implements \IteratorAggregate
{
    /** @var string */
    private $newsFeedUrl;

    /** @var NewsItemSlugger */
    private $slugger;

    /** @var HttpClientInterface */
    private $httpClient;

    /**
     * @param string $newsFeedUrl
     * @param NewsItemSlugger $slugger
     * @param HttpClientInterface $httpClient
     */
    public function __construct(string $newsFeedUrl, NewsItemSlugger $slugger, HttpClientInterface $httpClient)
    {
        $this->newsFeedUrl = $newsFeedUrl;
        $this->slugger = $slugger;
        $this->httpClient = $httpClient;
    }

    /**
     * @return \Traversable<NewsItem>
     */
    public function getIterator(): \Traversable
    {
        foreach ($this->fetchNewsFeed()->entry as $newsEntry) {
            if ($newsEntry->id === null
                || $newsEntry->title === null
                || $newsEntry->link === null
                || $newsEntry->link->attributes() === null
                || $newsEntry->summary === null
                || $newsEntry->author === null
                || $newsEntry->author->name === null
                || $newsEntry->author->uri === null
                || $newsEntry->updated === null
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
        $response = $this->httpClient->request('GET', $this->newsFeedUrl);
        $content = $response->getContent();

        libxml_use_internal_errors(true);
        $feed = simplexml_load_string($content);

        if (!$feed) {
            $error = libxml_get_last_error();
            if ($error) {
                throw new \RuntimeException($error->message, $error->code);
            }

            throw new \RuntimeException('empty news feed');
        }

        return $feed;
    }
}
