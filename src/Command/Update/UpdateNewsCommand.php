<?php

namespace App\Command\Update;

use App\Entity\NewsAuthor;
use App\Entity\NewsItem;
use Doctrine\ORM\EntityManagerInterface;
use FeedIo\Factory;
use FeedIo\Feed\ItemInterface;
use FeedIo\FeedInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateNewsCommand extends Command
{
    use LockableTrait;

    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var string */
    private $newsFeedUrl;

    /**
     * @param EntityManagerInterface $entityManager
     * @param string $newsFeedUrl
     */
    public function __construct(EntityManagerInterface $entityManager, string $newsFeedUrl)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->newsFeedUrl = $newsFeedUrl;
    }

    protected function configure()
    {
        $this->setName('app:update:news');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->lock('cron.lock', true);
        $this->updateNewsEntries($this->getNewsFeed());
        $this->release();
    }

    private function updateNewsEntries(FeedInterface $newsFeed)
    {
        /** @var ItemInterface $newsEntry */
        foreach ($newsFeed as $newsEntry) {
            $newsItem = $this->entityManager->find(NewsItem::class, $newsEntry->getPublicId());
            if (is_null($newsItem)) {
                $newsItem = new NewsItem($newsEntry->getPublicId());
            }
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
            $this->entityManager->persist($newsItem);
        }

        $this->entityManager->flush();
    }

    /**
     * @return FeedInterface
     */
    private function getNewsFeed(): FeedInterface
    {
        return Factory::create()->getFeedIo()->read($this->newsFeedUrl)->getFeed();
    }
}
