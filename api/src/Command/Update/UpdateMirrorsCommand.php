<?php

namespace App\Command\Update;

use App\Entity\Mirror;
use App\Repository\MirrorRepository;
use App\Service\MirrorFetcher;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UpdateMirrorsCommand extends Command
{
    use LockableTrait;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var MirrorFetcher */
    private $mirrorFetcher;

    /** @var MirrorRepository */
    private $mirrorRepository;

    /** @var ValidatorInterface */
    private $validator;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param EntityManagerInterface $entityManager
     * @param MirrorFetcher $mirrorFetcher
     * @param MirrorRepository $mirrorRepository
     * @param ValidatorInterface $validator
     * @param LoggerInterface $logger
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        MirrorFetcher $mirrorFetcher,
        MirrorRepository $mirrorRepository,
        ValidatorInterface $validator,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->mirrorFetcher = $mirrorFetcher;
        $this->mirrorRepository = $mirrorRepository;
        $this->validator = $validator;
        $this->logger = $logger;
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
        $this->lock('mirrors.lock');

        $urls = [];
        /** @var Mirror $mirror */
        foreach ($this->mirrorFetcher as $mirror) {
            $errors = $this->validator->validate($mirror);
            if ($errors->count() > 0) {
                $this->logger->error(
                    sprintf('Ignoring "%s" due to validation errors', $mirror->getUrl()),
                    ['errors' => $errors]
                );
                continue;
            }

            /** @var Mirror|null $persistedMirror */
            $persistedMirror = $this->mirrorRepository->find($mirror->getUrl());
            if ($persistedMirror) {
                $mirror = $persistedMirror->update($mirror);
            } else {
                $this->entityManager->persist($mirror);
            }

            $urls[] = $mirror->getUrl();
        }
        foreach ($this->mirrorRepository->findAllExceptByUrls($urls) as $mirror) {
            $this->entityManager->remove($mirror);
        }

        $this->entityManager->flush();
        $this->release();

        return Command::SUCCESS;
    }
}
