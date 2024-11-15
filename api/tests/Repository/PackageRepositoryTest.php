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
        $pacman = new Package(
            $coreRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        );
        $glibc = new Package(
            $coreRepository,
            'glibc',
            '2.26-10',
            Architecture::X86_64
        );
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
        $databasePacman = $packageRepository->findByRepositoryAndName($coreRepository, $pacman->getName());
        $this->assertInstanceOf(Package::class, $databasePacman);
        $this->assertEquals($pacman->getId(), $databasePacman->getId());
    }

    public function testFindLatestByArchitecture(): void
    {
        $entityManager = $this->getEntityManager();
        $coreRepository = new Repository('core', Architecture::X86_64);
        $pacman = (new Package(
            $coreRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        ))->setBuildDate(new \DateTime('2018-02-01'));
        $glibc = (new Package(
            $coreRepository,
            'glibc',
            '2.26-10',
            Architecture::X86_64
        ))->setBuildDate(new \DateTime('2018-01-01'));
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
        $testingRepository = (new Repository('core-testing', Architecture::X86_64))->setTesting();
        $pacman = new Package(
            $coreRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        );
        $testingPacman = new Package(
            $testingRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        );
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
     * @return list<mixed[]>
     */
    public static function provideTerms(): array
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
        $pacman = new Package(
            $coreRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        );
        $glibc = new Package(
            $coreRepository,
            'glibc',
            '2.26-10',
            Architecture::X86_64
        );
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
        $pacman = new Package(
            $coreRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        );
        $glibc = new Package(
            $coreRepository,
            'glibc',
            '2.26-10',
            Architecture::X86_64
        );
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

    public function testFindByRepositoryExceptNames(): void
    {
        $entityManager = $this->getEntityManager();

        $coreRepository = new Repository('core', Architecture::X86_64);
        $pacman = new Package(
            $coreRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        );
        $glibc = new Package(
            $coreRepository,
            'glibc',
            '2.26-10',
            Architecture::X86_64
        );
        $pacman->addDependency(new Dependency('glibc'));
        $entityManager->persist($coreRepository);
        $entityManager->persist($pacman);
        $entityManager->persist($glibc);
        $entityManager->flush();
        $entityManager->clear();

        /** @var PackageRepository $packageRepository */
        $packageRepository = $entityManager->getRepository(Package::class);
        $packages = $packageRepository->findByRepositoryExceptNames(
            $coreRepository,
            ['glibc']
        );
        $this->assertCount(1, $packages);
        $this->assertEquals($pacman->getId(), $packages[0]->getId());
    }

    public function testFindByRepositoryExceptNamesWithEmptyPackageList(): void
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
        $packages = $packageRepository->findByRepositoryExceptNames($coreRepository, []);
        $this->assertCount(1, $packages);
        $this->assertEquals($pacman->getId(), $packages[0]->getId());
    }
}
