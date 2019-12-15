<?php

namespace App\Service;

use App\Command\Exception\ValidationException;
use App\Entity\Packages\Repository;
use App\Repository\RepositoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RepositoryManager
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var array<array<string>> */
    private $repositoryConfiguration;

    /** @var RepositoryRepository */
    private $repositoryRepository;

    /** @var ValidatorInterface */
    private $validator;

    /**
     * @param EntityManagerInterface $entityManager
     * @param array<array<string>> $repositoryConfiguration
     * @param RepositoryRepository $repositoryRepository
     * @param ValidatorInterface $validator
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        array $repositoryConfiguration,
        RepositoryRepository $repositoryRepository,
        ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->repositoryConfiguration = $repositoryConfiguration;
        $this->repositoryRepository = $repositoryRepository;
        $this->validator = $validator;
    }

    /**
     * @return bool
     */
    public function removeObsoleteRepositories(): bool
    {
        $repositoryWasRemoved = false;

        /** @var Repository $repo */
        foreach ($this->repositoryRepository->findAll() as $repo) {
            if (
                !isset($this->repositoryConfiguration[$repo->getName()])
                || !in_array($repo->getArchitecture(), $this->repositoryConfiguration[$repo->getName()])
            ) {
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
                if ($repository === null) {
                    $repository = new Repository($repoName, $archName);
                    $repository->setTesting(preg_match('/(-|^)testing$/', $repoName) > 0);

                    $errors = $this->validator->validate($repository);
                    if ($errors->count() > 0) {
                        throw new ValidationException($errors);
                    }

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
