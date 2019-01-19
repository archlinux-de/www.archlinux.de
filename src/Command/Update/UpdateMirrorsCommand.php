<?php

namespace App\Command\Update;

use App\Entity\Mirror;
use App\Repository\MirrorRepository;
use App\Service\MirrorFetcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateMirrorsCommand extends Command
{
    use LockableTrait;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var MirrorFetcher */
    private $mirrorFetcher;

    /** @var MirrorRepository */
    private $mirrorRepository;

    /**
     * @param EntityManagerInterface $entityManager
     * @param MirrorFetcher $mirrorFetcher
     * @param MirrorRepository $mirrorRepository
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        MirrorFetcher $mirrorFetcher,
        MirrorRepository $mirrorRepository
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->mirrorFetcher = $mirrorFetcher;
        $this->mirrorRepository = $mirrorRepository;
    }

    protected function configure(): void
    {
        $this->setName('app:update:mirrors');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->lock('cron.lock', true);

        $urls = [];
        /** @var Mirror $mirror */
        foreach ($this->mirrorFetcher as $mirror) {
            $this->entityManager->merge($mirror);
            $urls[] = $mirror->getUrl();
        }
        foreach ($this->mirrorRepository->findAllExceptByUrls($urls) as $mirror) {
            $this->entityManager->remove($mirror);
        }

        $this->entityManager->flush();
        $this->release();

        return 0;
    }
}
