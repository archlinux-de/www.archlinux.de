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

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var RepositoryManager */
    private $repositoryManager;

    /** @var AbstractRelationRepository */
    private $relationRepository;

    /**
     * @param EntityManagerInterface $entityManager
     * @param RepositoryManager $repositoryManager
     * @param AbstractRelationRepository $relationRepository
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        RepositoryManager $repositoryManager,
        AbstractRelationRepository $relationRepository
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->repositoryManager = $repositoryManager;
        $this->relationRepository = $relationRepository;
    }

    protected function configure(): void
    {
        $this->setName('app:update:repositories');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->lock('packages.lock');
        ini_set('memory_limit', '-1');

        $this->repositoryManager->createNewRepositories();
        if ($this->repositoryManager->removeObsoleteRepositories()) {
            $this->relationRepository->updateTargets();
            $this->entityManager->flush();
        }

        $this->release();

        return 0;
    }
}
