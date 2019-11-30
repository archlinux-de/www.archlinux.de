<?php

namespace App\Command\Update;

use App\Command\Exception\ValidationException;
use App\Entity\Release;
use App\Repository\ReleaseRepository;
use App\Service\ReleaseFetcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UpdateReleasesCommand extends Command
{
    use LockableTrait;

    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var ReleaseFetcher */
    private $releaseFetcher;
    /** @var ReleaseRepository */
    private $releaseRepository;
    /** @var ValidatorInterface */
    private $validator;

    /**
     * @param EntityManagerInterface $entityManager
     * @param ReleaseFetcher $releaseFetcher
     * @param ReleaseRepository $releaseRepository
     * @param ValidatorInterface $validator
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ReleaseFetcher $releaseFetcher,
        ReleaseRepository $releaseRepository,
        ValidatorInterface $validator
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->releaseFetcher = $releaseFetcher;
        $this->releaseRepository = $releaseRepository;
        $this->validator = $validator;
    }

    protected function configure(): void
    {
        $this->setName('app:update:releases');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->lock('releases.lock');

        $versions = [];
        /** @var Release $release */
        foreach ($this->releaseFetcher as $release) {
            $errors = $this->validator->validate($release);
            if ($errors->count() > 0) {
                throw new ValidationException($errors);
            }

            /** @var Release|null $persistedRelease */
            $persistedRelease = $this->releaseRepository->find($release->getVersion());
            if ($persistedRelease) {
                $release = $persistedRelease->update($release);
            }

            $this->entityManager->persist($release);
            $versions[] = $release->getVersion();
        }
        foreach ($this->releaseRepository->findAllExceptByVersions($versions) as $release) {
            $this->entityManager->remove($release);
        }

        $this->entityManager->flush();
        $this->release();

        return 0;
    }
}
