<?php

namespace App\Tests\Repository;

use App\Entity\Packages\Architecture;
use App\Entity\Packages\Files;
use App\Entity\Packages\Package;
use App\Entity\Packages\Repository;
use App\Repository\FilesRepository;
use App\Repository\PackageRepository;
use SymfonyDatabaseTest\DatabaseTestCase;

class FilesRepositoryTest extends DatabaseTestCase
{
    public function testGetByPackageName(): void
    {
        $entityManager = $this->getEntityManager();

        $coreRepository = new Repository('core', Architecture::X86_64);
        $filesArray = ['usr/bin', 'usr/bin/pacman'];
        $pacman = new Package(
            $coreRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        );
        $pacman->setFiles(Files::createFromArray($filesArray));
        $entityManager->persist($coreRepository);
        $entityManager->persist($pacman);
        $entityManager->flush();
        $entityManager->clear();

        $filesRepository = $entityManager->getRepository(Files::class);
        $this->assertInstanceOf(FilesRepository::class, $filesRepository);
        $files = $filesRepository->getByPackageName('core', Architecture::X86_64, 'pacman');
        $this->assertEquals($filesArray, [...$files]);
    }

    public function testFileRelationIsRemovedWithPackage(): void
    {
        $entityManager = $this->getEntityManager();

        $coreRepository = new Repository('core', Architecture::X86_64);
        $pacman = new Package(
            $coreRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        );
        $pacman->setFiles(Files::createFromArray(['usr/bin', 'usr/bin/pacman']));
        $entityManager->persist($coreRepository);
        $entityManager->persist($pacman);
        $entityManager->flush();
        $entityManager->clear();

        $packageRepository = $entityManager->getRepository(Package::class);
        $this->assertInstanceOf(PackageRepository::class, $packageRepository);
        $persistedPacman = $packageRepository->find($pacman->getId());
        $this->assertInstanceOf(Package::class, $persistedPacman);
        $this->assertNotNull($persistedPacman);
        $persistedPacman->setFiles(Files::createFromArray(['ust/lib']));
        $entityManager->persist($persistedPacman);
        $entityManager->flush();
        $entityManager->clear();

        $filesRepository = $entityManager->getRepository(Files::class);
        $this->assertCount(1, $filesRepository->findAll());
    }
}
