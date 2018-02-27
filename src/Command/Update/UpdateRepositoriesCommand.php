<?php

namespace App\Command\Update;

use App\Repository\AbstractRelationRepository;
use App\Service\RepositoryManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateRepositoriesCommand extends ContainerAwareCommand
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

    protected function configure()
    {
        $this->setName('app:update:repositories');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->lock('cron.lock', true);
        ini_set('memory_limit', '-1');

        $this->repositoryManager->createNewRepositories();
        if ($this->repositoryManager->removeObsoleteRepositories()) {
            $this->relationRepository->updateTargets();
            $this->entityManager->flush();
        }

        $this->release();
    }
}
