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
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UpdateReleasesCommand extends Command
{
    use LockableTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ReleaseFetcher $releaseFetcher,
        private ReleaseRepository $releaseRepository,
        private ValidatorInterface $validator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('app:update:releases');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->lock('releases.lock');

        $versions = [];
        /** @var Release $release */
        foreach ($this->releaseFetcher as $release) {
            $errors = $this->validator->validate($release);
            if ($errors->count() > 0) {
                throw new ValidationFailedException($release, $errors);
            }

            /** @var Release|null $persistedRelease */
            $persistedRelease = $this->releaseRepository->find($release->getVersion());
            if ($persistedRelease) {
                $release = $persistedRelease->update($release);
            } else {
                $this->entityManager->persist($release);
            }

            $versions[] = $release->getVersion();
        }
        foreach ($this->releaseRepository->findAllExceptByVersions($versions) as $release) {
            $this->entityManager->remove($release);
        }

        $this->entityManager->flush();
        $this->release();

        return Command::SUCCESS;
    }
}
