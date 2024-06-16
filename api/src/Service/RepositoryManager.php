<?php

namespace App\Service;

use App\Entity\Packages\Repository;
use App\Repository\RepositoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RepositoryManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly array $repositoryConfiguration,
        private readonly RepositoryRepository $repositoryRepository,
        private readonly ValidatorInterface $validator
    ) {
    }

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

    public function createNewRepositories(): bool
    {
        $repositoryWasCreated = false;

        foreach ($this->repositoryConfiguration as $repoName => $archNames) {
            foreach ($archNames as $archName) {
                $repository = $this->repositoryRepository->findByNameAndArchitecture($repoName, $archName);
                if ($repository === null) {
                    $repository = new Repository($repoName, $archName);
                    $repository->setTesting(str_ends_with($repoName, '-testing'));

                    $errors = $this->validator->validate($repository);
                    if ($errors->count() > 0) {
                        throw new ValidationFailedException($repository, $errors);
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
