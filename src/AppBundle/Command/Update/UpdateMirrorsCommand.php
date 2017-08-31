<?php

namespace AppBundle\Command\Update;

use AppBundle\Entity\Country;
use AppBundle\Entity\Mirror;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateMirrorsCommand extends Command
{
    use LockableTrait;

    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var Client */
    private $guzzleClient;
    /** @var string */
    private $mirrorStatusUrl;

    /**
     * @param EntityManagerInterface $entityManager
     * @param Client $guzzleClient
     * @param string $mirrorStatusUrl
     */
    public function __construct(EntityManagerInterface $entityManager, Client $guzzleClient, string $mirrorStatusUrl)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->guzzleClient = $guzzleClient;
        $this->mirrorStatusUrl = $mirrorStatusUrl;
    }

    protected function configure()
    {
        $this->setName('app:update:mirrors');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->lock('cron.lock', true);

        try {
            $status = $this->getMirrorStatus();
            if ($status['version'] != 3) {
                throw new \RuntimeException('incompatible mirrorstatus version');
            }
            $mirrors = $status['urls'];
            if (empty($mirrors)) {
                throw new \RuntimeException('mirrorlist is empty');
            }
            $this->entityManager->beginTransaction();
            $this->updateMirrorlist($mirrors);
            $this->entityManager->commit();
        } catch (\RuntimeException $e) {
            $this->entityManager->rollBack();
            $output->writeln('Warning: UpdateMirrors failed: ' . $e->getMessage());
        }
    }

    /**
     * @param array $mirrors
     */
    private function updateMirrorlist(array $mirrors)
    {
        $this->removeAllMirrors();

        foreach ($mirrors as $mirror) {
            $newMirror = new Mirror($mirror['url'], $mirror['protocol']);

            if (!is_null($mirror['country_code'])) {
                $country = $this->entityManager->find(Country::class, $mirror['country_code']);
                $newMirror->setCountry($country);
            }
            if (!is_null($mirror['last_sync'])) {
                $newMirror->setLastSync(new \DateTime($mirror['last_sync']));
            }
            $newMirror->setDelay($mirror['delay']);
            $newMirror->setDurationAvg($mirror['duration_avg']);
            $newMirror->setScore($mirror['score']);
            $newMirror->setCompletionPct($mirror['completion_pct']);
            $newMirror->setDurationStddev($mirror['duration_stddev']);

            $this->entityManager->persist($newMirror);
        }

        $this->entityManager->flush();
    }

    private function removeAllMirrors()
    {
        $mirrors = $this->entityManager->getRepository(Mirror::class)->findAll();
        foreach ($mirrors as $mirror) {
            $this->entityManager->remove($mirror);
        }
        $this->entityManager->flush();
    }

    private function getMirrorStatus(): array
    {
        $response = $this->guzzleClient->request('GET', $this->mirrorStatusUrl);
        $content = $response->getBody()->getContents();
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
