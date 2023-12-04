<?php

namespace App\Command\Update;

use App\Repository\AbstractRelationRepository;
use App\Service\RepositoryManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateRepositoriesCommand extends Command
{
    use LockableTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private RepositoryManager $repositoryManager,
        private AbstractRelationRepository $relationRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('app:update:repositories');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->lock('packages.lock');
        ini_set('memory_limit', '8G');

        $this->repositoryManager->createNewRepositories();
        if ($this->repositoryManager->removeObsoleteRepositories()) {
            $this->relationRepository->updateTargets();
            $this->entityManager->flush();
        }

        $this->release();

        return Command::SUCCESS;
    }
}
