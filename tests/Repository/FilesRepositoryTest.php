<?php

namespace App\Tests\Repository;

use App\Entity\Packages\Architecture;
use App\Entity\Packages\Files;
use App\Entity\Packages\Package;
use App\Entity\Packages\Repository;
use App\Tests\Util\DatabaseTestCase;

class FilesRepositoryTest extends DatabaseTestCase
{
    public function testGetByPackageName()
    {
        $entityManager = $this->getEntityManager();

        $coreRepository = new Repository('core', Architecture::X86_64);
        $filesArray = ['usr/bin', 'usr/bin/pacman'];
        $pacman = (new Package(
            $coreRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        ))->setMTime(new \DateTime());
        $pacman->setFiles(Files::createFromArray($filesArray));
        $entityManager->persist($coreRepository);
        $entityManager->persist($pacman);
        $entityManager->flush();
        $entityManager->clear();

        $filesRepository = $entityManager->getRepository(Files::class);
        $files = $filesRepository->getByPackageName('core', Architecture::X86_64, 'pacman');
        $this->assertEquals($filesArray, iterator_to_array($files));
    }
}
