<?php

namespace App\Tests\Repository;

use App\Entity\Packages\Architecture;
use App\Entity\Packages\Package;
use App\Entity\Packages\Relations\AbstractRelation;
use App\Entity\Packages\Relations\Dependency;
use App\Entity\Packages\Relations\Provision;
use App\Entity\Packages\Repository;
use App\Tests\Util\DatabaseTestCase;

class AbstractRelationRepositoryTest extends DatabaseTestCase
{
    public function testDependencyIsUpdated()
    {
        $entityManager = $this->getEntityManager();

        $coreRepository = new Repository('core', Architecture::X86_64);
        $pacman = (new Package(
            $coreRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        ))->setMTime(new \DateTime());
        $glibc = (new Package(
            $coreRepository,
            'glibc',
            '2.26-10',
            Architecture::X86_64
        ))->setMTime(new \DateTime());
        $pacman->addDependency(new Dependency('glibc'));
        $entityManager->persist($coreRepository);
        $entityManager->persist($pacman);
        $entityManager->persist($glibc);
        $entityManager->flush();

        $entityManager->getRepository(AbstractRelation::class)->updateTargets();

        $entityManager->flush();
        $entityManager->clear();

        $packageRepository = $entityManager->getRepository(Package::class);
        $databasePacman = $packageRepository->find($pacman->getId());
        $databaseGlibc = $databasePacman->getDependencies()->first()->getTarget();
        $this->assertEquals($glibc->getId(), $databaseGlibc->getId());
    }

    public function testProvisionIsUpdated()
    {
        $entityManager = $this->getEntityManager();

        $coreRepository = new Repository('core', Architecture::X86_64);
        $pacman = (new Package(
            $coreRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        ))->setMTime(new \DateTime());
        $glibc = (new Package(
            $coreRepository,
            'glibc-ng',
            '1.0-1',
            Architecture::X86_64
        ))->setMTime(new \DateTime());
        $glibc->addProvision(new Provision('glibc'));
        $pacman->addDependency(new Dependency('glibc'));
        $entityManager->persist($coreRepository);
        $entityManager->persist($pacman);
        $entityManager->persist($glibc);
        $entityManager->flush();

        $entityManager->getRepository(AbstractRelation::class)->updateTargets();

        $entityManager->flush();
        $entityManager->clear();

        $packageRepository = $entityManager->getRepository(Package::class);
        $databasePacman = $packageRepository->find($pacman->getId());
        $databaseGlibc = $databasePacman->getDependencies()->first()->getTarget();
        $this->assertEquals($glibc->getId(), $databaseGlibc->getId());
    }

    public function testDependencyFromAnotherRepositoryIsUpdated()
    {
        $entityManager = $this->getEntityManager();

        $coreRepository = new Repository('core', Architecture::X86_64);
        $extraRepository = new Repository('extra', Architecture::X86_64);
        $testingRepository = (new Repository('testing', Architecture::X86_64))->setTesting();
        $pacman = (new Package(
            $extraRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        ))->setMTime(new \DateTime());
        $glibc = (new Package(
            $coreRepository,
            'glibc',
            '2.26-10',
            Architecture::X86_64
        ))->setMTime(new \DateTime());
        $testingGlibc = (new Package(
            $testingRepository,
            'glibc',
            '3.0-1',
            Architecture::X86_64
        ))->setMTime(new \DateTime());
        $pacman->addDependency(new Dependency('glibc'));
        $entityManager->persist($coreRepository);
        $entityManager->persist($extraRepository);
        $entityManager->persist($testingRepository);
        $entityManager->persist($pacman);
        $entityManager->persist($glibc);
        $entityManager->persist($testingGlibc);
        $entityManager->flush();

        $entityManager->getRepository(AbstractRelation::class)->updateTargets();

        $entityManager->flush();
        $entityManager->clear();

        $packageRepository = $entityManager->getRepository(Package::class);
        $databasePacman = $packageRepository->find($pacman->getId());
        $databaseGlibc = $databasePacman->getDependencies()->first()->getTarget();
        $this->assertEquals($glibc->getId(), $databaseGlibc->getId());
    }

    public function testAmbiguousProvisionIsIgnored()
    {
        $entityManager = $this->getEntityManager();

        $coreRepository = new Repository('core', Architecture::X86_64);
        $pacman = (new Package(
            $coreRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        ))->setMTime(new \DateTime());
        $glibc4 = (new Package(
            $coreRepository,
            'glibc4',
            '4.0-1',
            Architecture::X86_64
        ))->setMTime(new \DateTime());
        $glibcNg = (new Package(
            $coreRepository,
            'glibc-ng',
            '1.0-1',
            Architecture::X86_64
        ))->setMTime(new \DateTime());
        $glibc4->addProvision(new Provision('glibc'));
        $glibcNg->addProvision(new Provision('glibc'));
        $pacman->addDependency(new Dependency('glibc'));
        $entityManager->persist($coreRepository);
        $entityManager->persist($pacman);
        $entityManager->persist($glibc4);
        $entityManager->persist($glibcNg);
        $entityManager->flush();

        $entityManager->getRepository(AbstractRelation::class)->updateTargets();

        $entityManager->flush();
        $entityManager->clear();

        $packageRepository = $entityManager->getRepository(Package::class);
        $databasePacman = $packageRepository->find($pacman->getId());
        $databaseGlibc = $databasePacman->getDependencies()->first()->getTarget();
        $this->assertNull($databaseGlibc);
    }
}
