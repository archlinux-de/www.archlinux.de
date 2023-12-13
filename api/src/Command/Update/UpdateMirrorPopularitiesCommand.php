<?php

namespace App\Command\Update;

use App\Entity\Mirror;
use App\Entity\MirrorPopularity as Popularity;
use App\Repository\MirrorRepository;
use App\Service\MirrorPopularityFetcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UpdateMirrorPopularitiesCommand extends Command
{
    use LockableTrait;

    private array $mirrorPopularities = [];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private MirrorPopularityFetcher $mirrorPopularityFetcher,
        private MirrorRepository $mirrorRepository,
        private ValidatorInterface $validator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('app:update:mirror-popularities');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->lock('mirrors.lock');
        ini_set('memory_limit', '4G');

        /**
         * @var string $url
         * @var Popularity $popularity
         */
        foreach ($this->mirrorPopularityFetcher as $url => $popularity) {
            $errors = $this->validator->validate($popularity);
            if ($errors->count() > 0) {
                throw new ValidationFailedException($popularity, $errors);
            }
            $this->mirrorPopularities[$url] = $popularity;
        }

        /** @var Mirror $mirror */
        foreach ($this->mirrorRepository->findAll() as $mirror) {
            $mirror->setPopularity($this->mirrorPopularities[$mirror->getUrl()] ?? null);
        }

        $this->entityManager->flush();
        $this->release();

        return Command::SUCCESS;
    }
}