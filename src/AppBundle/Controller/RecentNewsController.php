<?php

namespace AppBundle\Controller;

use FeedIo\Factory;
use FeedIo\Feed;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RecentNewsController extends Controller
{
    /** @var Packages */
    private $assetPackages;

    /**
     * @param Packages $assetPackages
     */
    public function __construct(Packages $assetPackages)
    {
        $this->assetPackages = $assetPackages;
    }

    /**
     * @Route(
     *     "/news/feed.{_format}",
     *     methods={"GET"},
     *     defaults={"_format": "atom"},
     *     requirements={"_format": "atom|rss|json"}
     * )
     * @param string $_format
     * @return Response
     */
    public function indexAction(string $_format): Response
    {
        $news = $this->getDoctrine()->getConnection()->query('
            SELECT
                id,
                link,
                title,
                updated,
                summary,
                author_name,
                author_uri
            FROM
                news_feed
            ORDER BY
                updated DESC
            LIMIT 25
            ');

        $feed = new Feed();
        $feedUrl = $this->generateUrl('app_recentnews_index', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $feed->setUrl($feedUrl);
        $feed->setTitle('Aktuelle Arch Linux Neuigkeiten');
        $feed->setPublicId($feedUrl);
        $feed->setLink($this->generateUrl('app_start_index', [], UrlGeneratorInterface::ABSOLUTE_URL));

        $icon = $feed->newElement();
        $icon->setName('icon')->setValue($this->assetPackages->getUrl('build/images/favicon.ico'));
        $feed->addElement($icon);

        $logo = $feed->newElement();
        $logo->setName('logo')->setValue($this->assetPackages->getUrl('build/images/archlogo.svg'));
        $feed->addElement($logo);

        foreach ($news as $newsItem) {
            $item = $feed->newItem();
            $item->setPublicId($newsItem['id']);
            $item->setTitle($newsItem['title']);
            $item->setLastModified((new \DateTime())->setTimestamp($newsItem['updated']));
            $author = $item->newAuthor();
            $author->setName($newsItem['author_name']);
            $author->setUri($newsItem['author_uri']);
            $item->setAuthor($author);
            $item->setLink($newsItem['link']);
            $item->setDescription($newsItem['summary']);

            $feed->add($item);
        }

        $feedIo = Factory::create()->getFeedIo();
        return (new Response($feedIo->format($feed, $_format)))->setSharedMaxAge(600);
    }
}
