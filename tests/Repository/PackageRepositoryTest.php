<?php

namespace App\Tests\Repository;

use App\Entity\Packages\Architecture;
use App\Entity\Packages\Package;
use App\Entity\Packages\Relations\AbstractRelation;
use App\Entity\Packages\Relations\Dependency;
use App\Entity\Packages\Repository;
use App\Repository\AbstractRelationRepository;
use App\Repository\PackageRepository;
use SymfonyDatabaseTest\DatabaseTestCase;

class PackageRepositoryTest extends DatabaseTestCase
{
    public function testFindByInverseRelation(): void
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

        /** @var AbstractRelationRepository $abstractRelationRepository */
        $abstractRelationRepository = $entityManager->getRepository(AbstractRelation::class);
        $abstractRelationRepository->updateTargets();

        $entityManager->flush();
        $entityManager->clear();

        /** @var PackageRepository $packageRepository */
        $packageRepository = $entityManager->getRepository(Package::class);
        $inverseRelations = $packageRepository->findByInverseRelationType($glibc, Dependency::class);
        $this->assertCount(1, $inverseRelations);
        $inverseRelation = array_shift($inverseRelations);
        $this->assertInstanceOf(Package::class, $inverseRelation);
        $this->assertEquals($pacman->getName(), $inverseRelation->getName());
    }

    public function testFindByRepositoryAndName(): void
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

        /** @var PackageRepository $packageRepository */
        $packageRepository = $entityManager->getRepository(Package::class);
        $databasePacman = $packageRepository->findByRepositoryAndName($coreRepository, $pacman->getName());
        $this->assertInstanceOf(Package::class, $databasePacman);
        $this->assertEquals($pacman->getId(), $databasePacman->getId());
    }

    public function testGetMaxMTimeByRepository(): void
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

        /** @var PackageRepository $packageRepository */
        $packageRepository = $entityManager->getRepository(Package::class);
        $mtime = $packageRepository->getMaxMTimeByRepository($coreRepository);
        $this->assertEquals($newMtime, $mtime);
    }

    public function testFindByRepositoryOlderThan(): void
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

        /** @var PackageRepository $packageRepository */
        $packageRepository = $entityManager->getRepository(Package::class);
        $packages = $packageRepository->findByRepositoryOlderThan($coreRepository, $oldMtime);
        $this->assertCount(1, $packages);
        $package = array_shift($packages);
        $this->assertInstanceOf(Package::class, $package);
        $this->assertEquals($pacman->getId(), $package->getId());
    }

    public function testFindLatestByArchitecture(): void
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

        /** @var PackageRepository $packageRepository */
        $packageRepository = $entityManager->getRepository(Package::class);
        $packages = $packageRepository->findLatestByArchitecture(Architecture::X86_64, 1);
        $this->assertCount(1, $packages);
        $this->assertEquals($pacman->getId(), $packages[0]->getId());
    }

    public function testFindStableByArchitecture(): void
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

        /** @var PackageRepository $packageRepository */
        $packageRepository = $entityManager->getRepository(Package::class);
        $packages = $packageRepository->findStableByArchitecture(Architecture::X86_64);
        $this->assertCount(1, $packages);
        $this->assertEquals($pacman->getId(), $packages[0]->getId());
    }

    /**
     * @param string $term
     * @param int $limit
     * @param int $matchCount
     * @dataProvider provideTerms
     */
    public function testFindByTerm(string $term, int $limit, int $matchCount): void
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

        /** @var PackageRepository $packageRepository */
        $packageRepository = $entityManager->getRepository(Package::class);
        $matches = $packageRepository->findByTerm($term, $limit);
        $this->assertCount($matchCount, $matches);
    }

    /**
     * @return array<array<int|string>>
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

    public function testGetByName(): void
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

        /** @var PackageRepository $packageRepository */
        $packageRepository = $entityManager->getRepository(Package::class);
        $package = $packageRepository->getByName('core', Architecture::X86_64, 'pacman');
        $this->assertEquals($pacman->getId(), $package->getId());
    }

    public function testGetSize(): void
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

        /** @var PackageRepository $packageRepository */
        $packageRepository = $entityManager->getRepository(Package::class);
        $this->assertEquals(2, $packageRepository->getSize());
    }

    public function testFindByRepository(): void
    {
        $entityManager = $this->getEntityManager();

        $coreRepository = new Repository('core', Architecture::X86_64);
        $pacman = new Package(
            $coreRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        );
        $entityManager->persist($coreRepository);
        $entityManager->persist($pacman);
        $entityManager->flush();
        $entityManager->clear();

        /** @var PackageRepository $packageRepository */
        $packageRepository = $entityManager->getRepository(Package::class);
        $packages = $packageRepository->findByRepository($coreRepository);
        $this->assertCount(1, $packages);
        $this->assertEquals($pacman->getId(), $packages[0]->getId());
    }

    public function testGetByRepositoryArchitectureAndName(): void
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

        /** @var PackageRepository $packageRepository */
        $packageRepository = $entityManager->getRepository(Package::class);
        $package = $packageRepository->getByRepositoryArchitectureAndName(
            $coreRepository->getArchitecture(),
            $pacman->getName()
        );
        $this->assertEquals($pacman->getId(), $package->getId());
    }
}
