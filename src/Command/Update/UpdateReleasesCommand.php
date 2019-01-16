<?php

namespace App\Command\Update;

use App\Entity\Release;
use App\Repository\ReleaseRepository;
use App\Service\ReleaseFetcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateReleasesCommand extends Command
{
    use LockableTrait;

    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var ReleaseFetcher */
    private $releaseFetcher;
    /** @var ReleaseRepository */
    private $releaseRepository;

    /**
     * @param EntityManagerInterface $entityManager
     * @param ReleaseFetcher $releaseFetcher
     * @param ReleaseRepository $releaseRepository
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ReleaseFetcher $releaseFetcher,
        ReleaseRepository $releaseRepository
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->releaseFetcher = $releaseFetcher;
        $this->releaseRepository = $releaseRepository;
    }

    protected function configure(): void
    {
        $this->setName('app:update:releases');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->lock('cron.lock', true);

        $versions = [];
        /** @var Release $release */
        foreach ($this->releaseFetcher as $release) {
            $this->entityManager->merge($release);
            $versions[] = $release->getVersion();
        }
        foreach ($this->releaseRepository->findAllExceptByVersions($versions) as $release) {
            $this->entityManager->remove($release);
        }

        $this->entityManager->flush();
        $this->release();
    }
}
