<?php

namespace App\Tests\Repository;

use App\Entity\Packages\Architecture;
use App\Entity\Packages\Repository;
use App\Repository\RepositoryRepository;
use SymfonyDatabaseTest\DatabaseTestCase;

class RepositoryRepositoryTest extends DatabaseTestCase
{
    public function testFindByNameAndArchitecture()
    {
        $repository = new Repository('core', Architecture::X86_64);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($repository);
        $entityManager->flush();
        $entityManager->clear();

        /** @var RepositoryRepository $repositoryRepository */
        $repositoryRepository = $this->getRepository(Repository::class);
        $databaseRepository = $repositoryRepository->findByNameAndArchitecture('core', Architecture::X86_64);
        $this->assertInstanceOf(Repository::class, $databaseRepository);
        $this->assertEquals($repository->getId(), $databaseRepository->getId());
    }
}
