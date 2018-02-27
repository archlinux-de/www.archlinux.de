<?php

namespace App\Service;

use App\Entity\Packages\Repository;
use App\Repository\RepositoryRepository;
use Doctrine\ORM\EntityManagerInterface;

class RepositoryManager
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var array */
    private $repositoryConfiguration;

    /** @var RepositoryRepository */
    private $repositoryRepository;

    /**
     * @param EntityManagerInterface $entityManager
     * @param array $repositoryConfiguration
     * @param RepositoryRepository $repositoryRepository
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        array $repositoryConfiguration,
        RepositoryRepository $repositoryRepository
    ) {
        $this->entityManager = $entityManager;
        $this->repositoryConfiguration = $repositoryConfiguration;
        $this->repositoryRepository = $repositoryRepository;
    }

    /**
     * @return bool
     */
    public function removeObsoleteRepositories(): bool
    {
        $repositoryWasRemoved = false;

        /** @var Repository $repo */
        foreach ($this->repositoryRepository->findAll() as $repo) {
            if (!isset($this->repositoryConfiguration[$repo->getName()])
                || !in_array($repo->getArchitecture(), $this->repositoryConfiguration[$repo->getName()])) {
                $this->entityManager->remove($repo);
                $repositoryWasRemoved = true;
            }
        }

        if ($repositoryWasRemoved) {
            $this->entityManager->flush();
        }

        return $repositoryWasRemoved;
    }

    /**
     * @return bool
     */
    public function createNewRepositories(): bool
    {
        $repositoryWasCreated = false;

        foreach ($this->repositoryConfiguration as $repoName => $archNames) {
            foreach ($archNames as $archName) {
                $repository = $this->repositoryRepository->findByNameAndArchitecture($repoName, $archName);
                if (is_null($repository)) {
                    $repository = new Repository($repoName, $archName);
                    $repository->setTesting(preg_match('/(-|^)testing$/', $repoName) > 0);
                    $this->entityManager->persist($repository);
                    $repositoryWasCreated = true;
                }
            }
        }

        if ($repositoryWasCreated) {
            $this->entityManager->flush();
        }

        return $repositoryWasCreated;
    }
}
