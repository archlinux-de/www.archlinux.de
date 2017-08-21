<?php

namespace AppBundle\Command\Update;

use Doctrine\DBAL\Driver\Connection;
use FeedIo\Factory;
use FeedIo\Feed\ItemInterface;
use FeedIo\FeedInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateNewsCommand extends ContainerAwareCommand
{
    use LockableTrait;

    /** @var Connection */
    private $database;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        parent::__construct();
        $this->database = $connection;
    }

    protected function configure()
    {
        $this->setName('app:update:news');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->lock('cron.lock', true);

        try {
            $this->database->beginTransaction();
            $this->updateNewsEntries($this->getNewsFeed());
            $this->database->commit();
        } catch (\RuntimeException $e) {
            $this->database->rollBack();
            $output->writeln('Warning: UpdateNews failed: ' . $e->getMessage());
        }
    }

    private function updateNewsEntries(FeedInterface $newsFeed)
    {
        $stm = $this->database->prepare('
                INSERT INTO
                    news_feed
                SET
                    id = :id,
                    title = :title,
                    link = :link,
                    summary = :summary,
                    author_name = :author_name,
                    author_uri = :author_uri,
                    updated = :updated
                ON DUPLICATE KEY UPDATE
                    title = VALUES(title),
                    summary = VALUES(summary),
                    author_name = VALUES(author_name),
                    updated = VALUES(updated)
            ');
        /** @var ItemInterface $newsEntry */
        foreach ($newsFeed as $newsEntry) {
            $stm->bindValue('id', $newsEntry->getPublicId(), \PDO::PARAM_STR);
            $stm->bindValue('title', $newsEntry->getTitle(), \PDO::PARAM_STR);
            $stm->bindValue('link', $newsEntry->getLink(), \PDO::PARAM_STR);
            $stm->bindValue('summary', $newsEntry->getDescription(), \PDO::PARAM_STR);
            $stm->bindValue('author_name', $newsEntry->getAuthor()->getName(), \PDO::PARAM_STR);
            $stm->bindValue('author_uri', $newsEntry->getAuthor()->getUri(), \PDO::PARAM_STR);
            $stm->bindValue('updated', $newsEntry->getLastModified()->getTimestamp(), \PDO::PARAM_INT);
            $stm->execute();
        }
    }

    /**
     * @return FeedInterface
     */
    private function getNewsFeed(): FeedInterface
    {
        return Factory::create()
            ->getFeedIo()
            ->read($this->getContainer()->getParameter('app.news.feed'))
            ->getFeed();
    }
}
