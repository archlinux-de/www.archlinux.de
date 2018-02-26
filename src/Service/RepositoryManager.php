<?php

namespace App\Service;

use App\Entity\Packages\Repository;
use App\Repository\RepositoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class RepositoryManager implements \IteratorAggregate
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var array */
    private $repositoryConfiguration;

    /** @var RepositoryRepository */
    private $repositoryRepository;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param EntityManagerInterface $entityManager
     * @param array $repositoryConfiguration
     * @param RepositoryRepository $repositoryRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        array $repositoryConfiguration,
        RepositoryRepository $repositoryRepository,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->repositoryConfiguration = $repositoryConfiguration;
        $this->repositoryRepository = $repositoryRepository;
        $this->logger = $logger;
    }

    public function cleanupObsoleteRepositories()
    {
        /** @var Repository[] $repos */
        $repos = $this->repositoryRepository->findAll();

        foreach ($repos as $repo) {
            if (!isset($this->repositoryConfiguration[$repo->getName()])
                || !in_array($repo->getArchitecture(), $this->repositoryConfiguration[$repo->getName()])) {
                $this->logger->info(
                    sprintf(
                        'Removing repository [%s] (%s)',
                        $repo->getName(),
                        $repo->getArchitecture()
                    )
                );
                $this->entityManager->remove($repo);
                $this->updatedPackages = true;
            }
        }
    }

    /**
     * @return \Iterator
     */
    public function getIterator(): \Iterator
    {
        foreach ($this->repositoryConfiguration as $repoName => $arches) {
            foreach ($arches as $arch) {
                yield $this->getRepository($repoName, $arch);
            }
        }
    }

    /**
     * @param string $repoName
     * @param string $archName
     * @return Repository
     */
    private function getRepository(string $repoName, string $archName): Repository
    {
        $repository = $this->repositoryRepository->findByNameAndArchitecture($repoName, $archName);
        if (is_null($repository)) {
            $repository = new Repository($repoName, $archName);
            $repository->setTesting(preg_match('/(-|^)testing$/', $repoName) > 0);
            $this->entityManager->persist($repository);
            $this->entityManager->flush();
        }
        return $repository;
    }
}
