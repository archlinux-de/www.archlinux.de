<?php

namespace App\Command\Update;

use App\Entity\Release;
use App\Entity\Torrent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use GuzzleHttp\Client;

class UpdateReleasesCommand extends Command
{
    use LockableTrait;

    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var Client */
    private $guzzleClient;
    /** @var string */
    private $releaseUrl;

    /**
     * @param EntityManagerInterface $entityManager
     * @param Client $guzzleClient
     * @param string $releaseUrl
     */
    public function __construct(EntityManagerInterface $entityManager, Client $guzzleClient, string $releaseUrl)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->guzzleClient = $guzzleClient;
        $this->releaseUrl = $releaseUrl;
    }

    protected function configure()
    {
        $this->setName('app:update:releases');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->lock('cron.lock', true);

        $releng = $this->getRelengReleases();
        if ($releng['version'] != 1) {
            throw new \RuntimeException('incompatible releng/releases version');
        }
        $releases = $releng['releases'];
        if (empty($releases)) {
            throw new \RuntimeException('there are no releases');
        }

        $this->updateRelengReleases($releases);
    }

    /**
     * @param array $newReleases
     */
    private function updateRelengReleases(array $newReleases)
    {
        foreach ($newReleases as $newRelease) {
            $release = $this->entityManager->find(Release::class, $newRelease['version']);

            if (is_null($release)) {
                $release = new Release($newRelease['version']);
            }
            $release
                ->setAvailable($newRelease['available'])
                ->setInfo($newRelease['info'])
                ->setIsoUrl($newRelease['iso_url'])
                ->setMd5Sum($newRelease['md5_sum'])
                ->setCreated(new \DateTime($newRelease['created']))
                ->setKernelVersion($newRelease['kernel_version'])
                ->setReleaseDate(new \DateTime($newRelease['release_date']))
                ->setSha1Sum($newRelease['sha1_sum'])
                ->setTorrent(
                    (new Torrent())
                        ->setUrl($newRelease['torrent_url'])
                        ->setComment($newRelease['torrent']['comment'])
                        ->setInfoHash($newRelease['torrent']['info_hash'])
                        ->setPieceLength($newRelease['torrent']['piece_length'])
                        ->setFileName($newRelease['torrent']['file_name'])
                        ->setAnnounce($newRelease['torrent']['announce'])
                        ->setFileLength($newRelease['torrent']['file_length'])
                        ->setPieceCount($newRelease['torrent']['piece_count'])
                        ->setCreatedBy($newRelease['torrent']['created_by'])
                        ->setCreationDate(new \DateTime($newRelease['torrent']['creation_date']))
                        ->setMagnetUri($newRelease['magnet_uri'])
                );

            $this->entityManager->persist($release);
        }

        $this->entityManager->flush();
    }

    /**
     * @return mixed
     */
    private function getRelengReleases(): array
    {
        $response = $this->guzzleClient->request('GET', $this->releaseUrl);
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
