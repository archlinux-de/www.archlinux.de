<?php

namespace AppBundle\Controller;

use AppBundle\Entity\NewsItem;
use Doctrine\ORM\EntityManagerInterface;
use FeedIo\Factory;
use FeedIo\Feed;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Asset\Packages;
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
     * @param EntityManagerInterface $entityManager
     * @param Packages $assetPackages
     * @return Response
     */
    public function indexAction(
        string $_format,
        EntityManagerInterface $entityManager,
        Packages $assetPackages
    ): Response {
        /** @var NewsItem[] $news */
        $news = $entityManager
            ->createQueryBuilder()
            ->select('news')
            ->from(NewsItem::class, 'news')
            ->orderBy('news.lastModified', 'DESC')
            ->setMaxResults(25)
            ->getQuery()
            ->getResult();

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
