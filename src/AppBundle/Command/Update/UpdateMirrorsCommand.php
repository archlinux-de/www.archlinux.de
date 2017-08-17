<?php

namespace AppBundle\Command\Update;

use archportal\lib\Config;
use archportal\lib\Download;
use Doctrine\DBAL\Driver\Connection;
use PDO;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateMirrorsCommand extends ContainerAwareCommand
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
        $this->setName('app:update:mirrors');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->lock('cron.lock', true);
        $this->getContainer()->get('AppBundle\Service\LegacyEnvironment')->initialize();

        try {
            $status = $this->getMirrorStatus();
            if ($status['version'] != 3) {
                throw new \RuntimeException('incompatible mirrorstatus version');
            }
            $mirrors = $status['urls'];
            if (empty($mirrors)) {
                throw new \RuntimeException('mirrorlist is empty');
            }
            $this->database->beginTransaction();
            $this->updateMirrorlist($mirrors);
            $this->database->commit();
        } catch (\RuntimeException $e) {
            $this->database->rollBack();
            $output->writeln('Warning: UpdateMirrors failed: ' . $e->getMessage());
        }
    }

    private function updateMirrorlist(array $mirrors)
    {
        $this->database->query('DELETE FROM mirrors');
        $stm = $this->database->prepare('
            INSERT INTO
                mirrors
            SET
                url = :url,
                protocol = :protocol,
                countryCode = :countryCode,
                lastsync = :lastsync,
                delay = :delay,
                durationAvg = :durationAvg,
                score = :score,
                completionPct = :completionPct,
                durationStddev = :durationStddev
            ');
        foreach ($mirrors as $mirror) {
            $stm->bindParam('url', $mirror['url'], PDO::PARAM_STR);
            $stm->bindParam('protocol', $mirror['protocol'], PDO::PARAM_STR);
            $stm->bindParam('countryCode', $mirror['country_code'], PDO::PARAM_STR);
            if (is_null($mirror['last_sync'])) {
                $lastSync = null;
            } else {
                $lastSyncDate = new \DateTime($mirror['last_sync']);
                $lastSync = $lastSyncDate->getTimestamp();
            }
            $stm->bindParam('lastsync', $lastSync, PDO::PARAM_INT);
            $stm->bindParam('delay', $mirror['delay'], PDO::PARAM_INT);
            $stm->bindParam('durationAvg', $mirror['duration_avg'], PDO::PARAM_STR);
            $stm->bindParam('score', $mirror['score'], PDO::PARAM_STR);
            $stm->bindParam('completionPct', $mirror['completion_pct'], PDO::PARAM_STR);
            $stm->bindParam('durationStddev', $mirror['duration_stddev'], PDO::PARAM_STR);
            $stm->execute();
        }
    }

    private function getMirrorStatus(): array
    {
        $download = new Download(Config::get('mirrors', 'status'));

        $content = file_get_contents($download->getFile());
        if (empty($content)) {
            throw new \RuntimeException('empty mirrorstatus', 1);
        }
        $mirrors = json_decode($content, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            throw new \RuntimeException('could not decode mirrorstatus', 1);
        }

        return $mirrors;
    }
}
