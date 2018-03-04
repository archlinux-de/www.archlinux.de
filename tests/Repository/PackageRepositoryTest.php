<?php

namespace App\Tests\Repository;

use App\Entity\Packages\Architecture;
use App\Entity\Packages\Package;
use App\Entity\Packages\Relations\AbstractRelation;
use App\Entity\Packages\Relations\Dependency;
use App\Entity\Packages\Repository;
use App\Tests\Util\DatabaseTestCase;

class PackageRepositoryTest extends DatabaseTestCase
{
    public function testFindByInverseRelation()
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
        $inverseRelations = $packageRepository->findByInverseRelationType($glibc, Dependency::class);
        $this->assertCount(1, $inverseRelations);
        $this->assertEquals($pacman->getName(), array_shift($inverseRelations)->getName());
    }

    public function testFindByRepositoryAndName()
    {
        $entityManager = $this->getEntityManager();

        $coreRepository = new Repository('core', Architecture::X86_64);
        $pacman = (new Package(
            $coreRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        ))->setMTime(new \DateTime());
        $entityManager->persist($coreRepository);
        $entityManager->persist($pacman);
        $entityManager->flush();
        $entityManager->clear();

        $packageRepository = $entityManager->getRepository(Package::class);
        $databasePacman = $packageRepository->findByRepositoryAndName($coreRepository, $pacman->getName());
        $this->assertEquals($pacman->getId(), $databasePacman->getId());
    }

    public function testGetMaxMTimeByRepository()
    {
        $entityManager = $this->getEntityManager();
        $oldMtime = new \DateTime('2018-01-01');
        $newMtime = new \DateTime('2018-02-01');

        $coreRepository = new Repository('core', Architecture::X86_64);
        $pacman = (new Package(
            $coreRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        ))->setMTime($oldMtime);
        $glibc = (new Package(
            $coreRepository,
            'glibc',
            '2.26-10',
            Architecture::X86_64
        ))->setMTime($newMtime);
        $entityManager->persist($coreRepository);
        $entityManager->persist($pacman);
        $entityManager->persist($glibc);
        $entityManager->flush();
        $entityManager->clear();

        $packageRepository = $entityManager->getRepository(Package::class);
        $mtime = $packageRepository->getMaxMTimeByRepository($coreRepository);
        $this->assertEquals($newMtime, $mtime);
    }

    public function testFindByRepositoryOlderThan()
    {
        $entityManager = $this->getEntityManager();
        $oldMtime = new \DateTime('2018-01-01');
        $newMtime = new \DateTime('2018-02-01');

        $coreRepository = new Repository('core', Architecture::X86_64);
        $pacman = (new Package(
            $coreRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        ))->setMTime($oldMtime);
        $glibc = (new Package(
            $coreRepository,
            'glibc',
            '2.26-10',
            Architecture::X86_64
        ))->setMTime($newMtime);
        $entityManager->persist($coreRepository);
        $entityManager->persist($pacman);
        $entityManager->persist($glibc);
        $entityManager->flush();
        $entityManager->clear();

        $packageRepository = $entityManager->getRepository(Package::class);
        $packages = $packageRepository->findByRepositoryOlderThan($coreRepository, $oldMtime);
        $this->assertCount(1, $packages);
        $this->assertEquals($pacman->getId(), array_shift($packages)->getId());
    }

    public function testFindLatestByArchitecture()
    {
        $entityManager = $this->getEntityManager();
        $oldMtime = new \DateTime('2018-01-01');
        $newMtime = new \DateTime('2018-02-01');

        $coreRepository = new Repository('core', Architecture::X86_64);
        $pacman = (new Package(
            $coreRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        ))->setMTime($newMtime)->setBuildDate($newMtime);
        $glibc = (new Package(
            $coreRepository,
            'glibc',
            '2.26-10',
            Architecture::X86_64
        ))->setMTime($oldMtime)->setBuildDate($oldMtime);
        $entityManager->persist($coreRepository);
        $entityManager->persist($pacman);
        $entityManager->persist($glibc);
        $entityManager->flush();
        $entityManager->clear();

        $packageRepository = $entityManager->getRepository(Package::class);
        $packages = $packageRepository->findLatestByArchitecture(Architecture::X86_64, 1);
        $this->assertCount(1, $packages);
        $this->assertEquals($pacman->getId(), array_shift($packages)->getId());
    }

    public function testFindStableByArchitecture()
    {
        $entityManager = $this->getEntityManager();

        $coreRepository = new Repository('core', Architecture::X86_64);
        $testingRepository = (new Repository('testing', Architecture::X86_64))->setTesting();
        $pacman = (new Package(
            $coreRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        ))->setMTime(new \DateTime());
        $testingPacman = (new Package(
            $testingRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        ))->setMTime(new \DateTime());
        $entityManager->persist($coreRepository);
        $entityManager->persist($testingRepository);
        $entityManager->persist($pacman);
        $entityManager->persist($testingPacman);
        $entityManager->flush();
        $entityManager->clear();

        $packageRepository = $entityManager->getRepository(Package::class);
        $packages = $packageRepository->findStableByArchitecture(Architecture::X86_64);
        $this->assertCount(1, $packages);
        $this->assertEquals($pacman->getId(), array_shift($packages)->getId());
    }

    /**
     * @param string $term
     * @param int $limt
     * @param int $matchCount
     * @dataProvider provideTerms
     */
    public function testFindByTerm(string $term, int $limit, int $matchCount)
    {
        $entityManager = $this->getEntityManager();

        $coreRepository = new Repository('core', Architecture::X86_64);
        $pacman = (new Package(
            $coreRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        ))->setMTime(new \DateTime());
        $pacwoman = (new Package(
            $coreRepository,
            'pacwoman',
            '5.0.2-2',
            Architecture::X86_64
        ))->setMTime(new \DateTime());
        $entityManager->persist($coreRepository);
        $entityManager->persist($pacman);
        $entityManager->persist($pacwoman);
        $entityManager->flush();
        $entityManager->clear();

        $packageRepository = $entityManager->getRepository(Package::class);
        $matches = $packageRepository->findByTerm($term, $limit);
        $this->assertCount($matchCount, $matches);
    }

    /**
     * @return array
     */
    public function provideTerms(): array
    {
        return [
            ['pac', 3, 2],
            ['pacm', 2, 1],
            ['foo', 2, 0],
            ['pac', 1, 1],
            ['pac', 0, 0]
        ];
    }

    public function testGetByName()
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
        $entityManager->clear();

        $packageRepository = $entityManager->getRepository(Package::class);
        $package = $packageRepository->getByName('core', Architecture::X86_64, 'pacman');
        $this->assertEquals($pacman->getId(), $package->getId());
    }

    public function testGetSize()
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
        $entityManager->clear();

        $packageRepository = $entityManager->getRepository(Package::class);
        $this->assertEquals(2, $packageRepository->getSize());
    }

    public function testFindByRepository()
    {
        $entityManager = $this->getEntityManager();

        $coreRepository = new Repository('core', Architecture::X86_64);
        $pacman = (new Package(
            $coreRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        ));
        $entityManager->persist($coreRepository);
        $entityManager->persist($pacman);
        $entityManager->flush();
        $entityManager->clear();

        $packageRepository = $entityManager->getRepository(Package::class);
        $packages = $packageRepository->findByRepository($coreRepository);
        $this->assertCount(1, $packages);
        $this->assertEquals($pacman->getId(), array_shift($packages)->getId());
    }
}
