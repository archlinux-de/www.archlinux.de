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

    public function __construct(
        private EntityManagerInterface $entityManager,
        private MirrorFetcher $mirrorFetcher,
        private MirrorRepository $mirrorRepository,
        private ValidatorInterface $validator,
        private LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('app:update:mirrors');
    }

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
