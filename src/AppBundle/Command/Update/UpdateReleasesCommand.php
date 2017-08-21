<?php

namespace AppBundle\Command\Update;

use Doctrine\DBAL\Driver\Connection;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use GuzzleHttp\Client;

class UpdateReleasesCommand extends ContainerAwareCommand
{
    use LockableTrait;

    /** @var Connection */
    private $database;
    /** @var Client */
    private $guzzleClient;

    /**
     * @param Connection $connection
     * @param Client $guzzleClient
     */
    public function __construct(Connection $connection, Client $guzzleClient)
    {
        parent::__construct();
        $this->database = $connection;
        $this->guzzleClient = $guzzleClient;
    }

    protected function configure()
    {
        $this->setName('app:update:releases');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->lock('cron.lock', true);

        try {
            $releng = $this->getRelengReleases();
            if ($releng['version'] != 1) {
                throw new \RuntimeException('incompatible releng/releases version');
            }
            $releases = $releng['releases'];
            if (empty($releases)) {
                throw new \RuntimeException('there are no releases');
            }
            $this->database->beginTransaction();
            $this->updateRelengReleases($releases);
            $this->database->commit();
        } catch (\RuntimeException $e) {
            $this->database->rollBack();
            $output->writeln('Warning: UpdateReleases failed: ' . $e->getMessage());
        }
    }

    /**
     * @param array $releases
     */
    private function updateRelengReleases(array $releases)
    {
        $this->database->query('DELETE FROM releng_releases');
        $stm = $this->database->prepare('
                INSERT INTO
                    releng_releases
                SET
                    version = :version,
                    available = :available,
                    info = :info,
                    iso_url = :iso_url,
                    md5_sum = :md5_sum,
                    created = :created,
                    kernel_version = :kernel_version,
                    release_date = :release_date,
                    torrent_url = :torrent_url,
                    sha1_sum = :sha1_sum,
                    torrent_comment = :torrent_comment,
                    torrent_info_hash = :torrent_info_hash,
                    torrent_piece_length = :torrent_piece_length,
                    torrent_file_name = :torrent_file_name,
                    torrent_announce = :torrent_announce,
                    torrent_file_length = :torrent_file_length,
                    torrent_piece_count = :torrent_piece_count,
                    torrent_created_by = :torrent_created_by,
                    torrent_creation_date = :torrent_creation_date,
                    magnet_uri = :magnet_uri
            ');
        foreach ($releases as $release) {
            $stm->bindParam('version', $release['version'], \PDO::PARAM_STR);
            $stm->bindValue('available', $release['available'] ? 1 : 0, \PDO::PARAM_INT);
            $stm->bindParam('info', $release['info'], \PDO::PARAM_STR);
            $stm->bindParam('iso_url', $release['iso_url'], \PDO::PARAM_STR);
            $stm->bindParam('md5_sum', $release['md5_sum'], \PDO::PARAM_STR);
            $stm->bindValue('created', $this->getTimestamp($release['created']), \PDO::PARAM_INT);
            $stm->bindParam('kernel_version', $release['kernel_version'], \PDO::PARAM_STR);
            $stm->bindParam('release_date', $release['release_date'], \PDO::PARAM_STR);
            $stm->bindParam('torrent_url', $release['torrent_url'], \PDO::PARAM_STR);
            $stm->bindParam('sha1_sum', $release['sha1_sum'], \PDO::PARAM_STR);
            $stm->bindValue(
                'torrent_comment',
                isset($release['torrent']['comment']) ? $release['torrent']['comment'] : null,
                \PDO::PARAM_STR
            );
            $stm->bindValue(
                'torrent_info_hash',
                isset($release['torrent']['info_hash']) ? $release['torrent']['info_hash'] : null,
                \PDO::PARAM_STR
            );
            $stm->bindValue(
                'torrent_piece_length',
                isset($release['torrent']['piece_length']) ? $release['torrent']['piece_length'] : null,
                \PDO::PARAM_INT
            );
            $stm->bindValue(
                'torrent_file_name',
                isset($release['torrent']['file_name']) ? $release['torrent']['file_name'] : null,
                \PDO::PARAM_STR
            );
            $stm->bindValue(
                'torrent_announce',
                isset($release['torrent']['announce']) ? $release['torrent']['announce'] : null,
                \PDO::PARAM_STR
            );
            $stm->bindValue(
                'torrent_file_length',
                isset($release['torrent']['file_length']) ? $release['torrent']['file_length'] : null,
                \PDO::PARAM_INT
            );
            $stm->bindValue(
                'torrent_piece_count',
                isset($release['torrent']['piece_count']) ? $release['torrent']['piece_count'] : null,
                \PDO::PARAM_INT
            );
            $stm->bindValue(
                'torrent_created_by',
                isset($release['torrent']['created_by']) ? $release['torrent']['created_by'] : null,
                \PDO::PARAM_STR
            );
            $stm->bindValue(
                'torrent_creation_date',
                isset($release['torrent']['creation_date'])
                    ? $this->getTimestamp($release['torrent']['creation_date']) : null,
                \PDO::PARAM_INT
            );
            $stm->bindParam('magnet_uri', $release['magnet_uri'], \PDO::PARAM_STR);
            $stm->execute();
        }
    }

    /**
     * @param string|null $data
     *
     * @return int|null
     */
    private function getTimestamp($data)
    {
        if (is_null($data)) {
            return null;
        } else {
            return (new \DateTime($data))->getTimestamp();
        }
    }

    /**
     * @return mixed
     */
    private function getRelengReleases(): array
    {
        $response = $this->guzzleClient->request(
            'GET',
            $this->getContainer()->getParameter('app.releng.releases')
        );
        $content = $response->getBody()->getContents();
        if (empty($content)) {
            throw new \RuntimeException('empty releng releases', 1);
        }
        $releng = json_decode($content, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            throw new \RuntimeException('could not decode releng releases', 1);
        }

        return $releng;
    }
}
