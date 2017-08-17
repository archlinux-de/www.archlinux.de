<?php

namespace AppBundle\Command\Update;

use archportal\lib\Config;
use archportal\lib\Database;
use archportal\lib\Download;
use PDO;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateNewsCommand extends ContainerAwareCommand
{
    use LockableTrait;

    protected function configure()
    {
        $this->setName('app:update:news');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->lock('cron.lock', true);
        $this->getContainer()->get('AppBundle\Service\LegacyEnvironment')->initialize();

        try {
            $newsEntries = $this->getNewsEntries();
            Database::beginTransaction();
            $this->updateNewsEntries($newsEntries);
            Database::commit();
        } catch (\RuntimeException $e) {
            Database::rollBack();
            $output->writeln('Warning: UpdateNews failed: ' . $e->getMessage());
        }
    }

    private function updateNewsEntries(\SimpleXMLElement $newsEntries)
    {
        $stm = Database::prepare('
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
        foreach ($newsEntries as $newsEntry) {
            $stm->bindParam('id', $newsEntry->id, PDO::PARAM_STR);
            $stm->bindParam('title', $newsEntry->title, PDO::PARAM_STR);
            $stm->bindParam('link', $newsEntry->link->attributes()->href, PDO::PARAM_STR);
            $stm->bindParam('summary', $newsEntry->summary, PDO::PARAM_STR);
            $stm->bindParam('author_name', $newsEntry->author->name, PDO::PARAM_STR);
            $stm->bindParam('author_uri', $newsEntry->author->uri, PDO::PARAM_STR);
            $stm->bindValue('updated', (new \DateTime((string)$newsEntry->updated))->getTimestamp(), PDO::PARAM_INT);
            $stm->execute();
        }
    }

    /**
     * @return \SimpleXMLElement
     */
    private function getNewsEntries(): \SimpleXMLElement
    {
        $download = new Download(Config::get('news', 'feed'));
        $feed = new \SimpleXMLElement($download->getFile(), 0, true);

        return $feed->entry;
    }
}
