<?php

namespace App\Controller;

use App\Entity\NewsItem;
use App\Repository\NewsItemRepository;
use FeedIo\Factory;
use FeedIo\Feed;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RecentNewsController extends Controller
{

    /**
     * @Route(
     *     "/news/feed.{_format}",
     *     methods={"GET"},
     *     defaults={"_format": "atom"},
     *     requirements={"_format": "atom|rss|json"}
     * )
     * @Cache(smaxage="600")
     * @param string $_format
     * @param NewsItemRepository $newsItemRepository
     * @param Packages $assetPackages
     * @return Response
     */
    public function indexAction(
        string $_format,
        NewsItemRepository $newsItemRepository,
        Packages $assetPackages
    ): Response {
        /** @var NewsItem[] $news */
        $news = $newsItemRepository->findLatest(25);

        $feed = new Feed();
        $feedUrl = $this->generateUrl('app_recentnews_index', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $feed->setUrl($feedUrl);
        $feed->setTitle('Aktuelle Arch Linux Neuigkeiten');
        $feed->setPublicId($feedUrl);
        $feed->setLink($this->generateUrl('app_start_index', [], UrlGeneratorInterface::ABSOLUTE_URL));

        $icon = $feed->newElement();
        $icon->setName('icon')->setValue($assetPackages->getUrl('build/images/archicon.svg'));
        $feed->addElement($icon);

        $logo = $feed->newElement();
        $logo->setName('logo')->setValue($assetPackages->getUrl('build/images/archicon.svg'));
        $feed->addElement($logo);

        foreach ($news as $newsItem) {
            $item = $feed->newItem();
            $item->setPublicId($newsItem->getId());
            $item->setTitle($newsItem->getTitle());
            $item->setLastModified($newsItem->getLastModified());
            $author = $item->newAuthor();
            $author->setName($newsItem->getAuthor()->getName());
            $author->setEmail('');
            $author->setUri($newsItem->getAuthor()->getUri());
            $item->setAuthor($author);
            $item->setLink($newsItem->getLink());
            $item->setDescription($newsItem->getDescription());

            $feed->add($item);
        }

        $feedIo = Factory::create()->getFeedIo();
        return (new Response($feedIo->format($feed, $_format)));
    }
}
