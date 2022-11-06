<?php

namespace App\Tests\Repository;

use App\Entity\Packages\Architecture;
use App\Entity\Packages\Package;
use App\Entity\Packages\Relations\AbstractRelation;
use App\Entity\Packages\Relations\Dependency;
use App\Entity\Packages\Relations\Provision;
use App\Entity\Packages\Repository;
use App\Repository\AbstractRelationRepository;
use SymfonyDatabaseTest\DatabaseTestCase;

class AbstractRelationRepositoryTest extends DatabaseTestCase
{
    public function testDependencyIsUpdated(): void
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

        $abstractRelationRepository = $entityManager->getRepository(AbstractRelation::class);
        $this->assertInstanceOf(AbstractRelationRepository::class, $abstractRelationRepository);
        $abstractRelationRepository->updateTargets();

        $entityManager->flush();
        $entityManager->clear();

        $packageRepository = $entityManager->getRepository(Package::class);
        $databasePacman = $packageRepository->find($pacman->getId());
        $this->assertInstanceOf(Package::class, $databasePacman);
        $this->assertInstanceOf(Dependency::class, $databasePacman->getDependencies()->first());
        $databaseGlibc = $databasePacman->getDependencies()->first()->getTarget();
        $this->assertInstanceOf(Package::class, $databaseGlibc);
        $this->assertEquals($glibc->getId(), $databaseGlibc->getId());
    }

    public function testProvisionIsUpdated(): void
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
            'glibc-ng',
            '1.0-1',
            Architecture::X86_64
        );
        $glibc->addProvision(new Provision('glibc'));
        $pacman->addDependency(new Dependency('glibc'));
        $entityManager->persist($coreRepository);
        $entityManager->persist($pacman);
        $entityManager->persist($glibc);
        $entityManager->flush();

        $abstractRelationRepository = $entityManager->getRepository(AbstractRelation::class);
        $this->assertInstanceOf(AbstractRelationRepository::class, $abstractRelationRepository);
        $abstractRelationRepository->updateTargets();

        $entityManager->flush();
        $entityManager->clear();

        $packageRepository = $entityManager->getRepository(Package::class);
        $databasePacman = $packageRepository->find($pacman->getId());
        $this->assertInstanceOf(Package::class, $databasePacman);
        $this->assertInstanceOf(Dependency::class, $databasePacman->getDependencies()->first());
        $databaseGlibc = $databasePacman->getDependencies()->first()->getTarget();
        $this->assertInstanceOf(Package::class, $databaseGlibc);
        $this->assertEquals($glibc->getId(), $databaseGlibc->getId());
    }

    public function testDependencyFromAnotherRepositoryIsUpdated(): void
    {
        $entityManager = $this->getEntityManager();

        $coreRepository = new Repository('core', Architecture::X86_64);
        $extraRepository = new Repository('extra', Architecture::X86_64);
        $testingRepository = (new Repository('testing', Architecture::X86_64))->setTesting();
        $pacman = new Package(
            $extraRepository,
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
        $testingGlibc = new Package(
            $testingRepository,
            'glibc',
            '3.0-1',
            Architecture::X86_64
        );
        $pacman->addDependency(new Dependency('glibc'));
        $entityManager->persist($coreRepository);
        $entityManager->persist($extraRepository);
        $entityManager->persist($testingRepository);
        $entityManager->persist($pacman);
        $entityManager->persist($glibc);
        $entityManager->persist($testingGlibc);
        $entityManager->flush();

        $abstractRelationRepository = $entityManager->getRepository(AbstractRelation::class);
        $this->assertInstanceOf(AbstractRelationRepository::class, $abstractRelationRepository);
        $abstractRelationRepository->updateTargets();

        $entityManager->flush();
        $entityManager->clear();

        $packageRepository = $entityManager->getRepository(Package::class);
        $databasePacman = $packageRepository->find($pacman->getId());
        $this->assertInstanceOf(Package::class, $databasePacman);
        $this->assertInstanceOf(Dependency::class, $databasePacman->getDependencies()->first());
        $databaseGlibc = $databasePacman->getDependencies()->first()->getTarget();
        $this->assertInstanceOf(Package::class, $databaseGlibc);
        $this->assertEquals($glibc->getId(), $databaseGlibc->getId());
    }

    public function testAmbiguousProvisionIsIgnored(): void
    {
        $entityManager = $this->getEntityManager();

        $coreRepository = new Repository('core', Architecture::X86_64);
        $pacman = new Package(
            $coreRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        );
        $glibc4 = new Package(
            $coreRepository,
            'glibc4',
            '4.0-1',
            Architecture::X86_64
        );
        $glibcNg = new Package(
            $coreRepository,
            'glibc-ng',
            '1.0-1',
            Architecture::X86_64
        );
        $glibc4->addProvision(new Provision('glibc'));
        $glibcNg->addProvision(new Provision('glibc'));
        $pacman->addDependency(new Dependency('glibc'));
        $entityManager->persist($coreRepository);
        $entityManager->persist($pacman);
        $entityManager->persist($glibc4);
        $entityManager->persist($glibcNg);
        $entityManager->flush();

        $abstractRelationRepository = $entityManager->getRepository(AbstractRelation::class);
        $this->assertInstanceOf(AbstractRelationRepository::class, $abstractRelationRepository);
        $abstractRelationRepository->updateTargets();

        $entityManager->flush();
        $entityManager->clear();

        $packageRepository = $entityManager->getRepository(Package::class);
        $databasePacman = $packageRepository->find($pacman->getId());
        $this->assertInstanceOf(Package::class, $databasePacman);
        $this->assertInstanceOf(Dependency::class, $databasePacman->getDependencies()->first());
        $databaseGlibc = $databasePacman->getDependencies()->first()->getTarget();
        $this->assertNull($databaseGlibc);
    }

    public function testProvisionHasCorrectArchitecture(): void
    {
        $entityManager = $this->getEntityManager();

        $core64Repository = new Repository('core', Architecture::X86_64);
        $core32Repository = new Repository('core', Architecture::I686);
        $pacman = new Package(
            $core64Repository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        );
        $glibcNg32 = new Package(
            $core32Repository,
            'glibc-ng',
            '1.0-1',
            Architecture::I686
        );
        $glibcNg64 = new Package(
            $core64Repository,
            'glibc-ng',
            '1.0-1',
            Architecture::X86_64
        );
        $glibcNg32->addProvision(new Provision('glibc'));
        $glibcNg64->addProvision(new Provision('glibc'));
        $pacman->addDependency(new Dependency('glibc'));
        $entityManager->persist($core64Repository);
        $entityManager->persist($core32Repository);
        $entityManager->persist($pacman);
        $entityManager->persist($glibcNg32);
        $entityManager->persist($glibcNg64);
        $entityManager->flush();

        $abstractRelationRepository = $entityManager->getRepository(AbstractRelation::class);
        $this->assertInstanceOf(AbstractRelationRepository::class, $abstractRelationRepository);
        $abstractRelationRepository->updateTargets();

        $entityManager->flush();
        $entityManager->clear();

        $packageRepository = $entityManager->getRepository(Package::class);
        $databasePacman = $packageRepository->find($pacman->getId());
        $this->assertInstanceOf(Package::class, $databasePacman);
        $this->assertInstanceOf(Dependency::class, $databasePacman->getDependencies()->first());
        $databaseGlibc = $databasePacman->getDependencies()->first()->getTarget();
        $this->assertInstanceOf(Package::class, $databaseGlibc);
        $this->assertEquals($glibcNg64->getId(), $databaseGlibc->getId());
    }

    public function testLibraryProvisionsWithVersions(): void
    {
        $entityManager = $this->getEntityManager();

        $coreRepository = new Repository('core', Architecture::X86_64);
        $systemd = new Package(
            $coreRepository,
            'systemd',
            '251.7-4',
            Architecture::X86_64
        );
        $openssl = new Package(
            $coreRepository,
            'openssl',
            '3.0.7-2',
            Architecture::X86_64
        );
        $lib32Openssl = new Package(
            $coreRepository,
            'lib32-openssl',
            '1:3.0.7-1',
            Architecture::X86_64
        );
        $openssl1 = new Package(
            $coreRepository,
            'openssl-1.1',
            '1.1.1.s-2',
            Architecture::X86_64
        );
        $openssl
            ->addProvision(new Provision('libcrypto.so', '=3-64'))
            ->addProvision(new Provision('libssl.so', '=3-64'));
        $lib32Openssl
            ->addProvision(new Provision('libcrypto.so', '=3-32'))
            ->addProvision(new Provision('libssl.so', '=3-32'));
        $openssl1
            ->addProvision(new Provision('libcrypto.so', '=1.1-64'))
            ->addProvision(new Provision('libssl.so', '=1.1-64'));
        $systemd
            ->addDependency(new Dependency('openssl'))
            ->addDependency(new Dependency('libcrypto.so', '=3-64'))
            ->addDependency(new Dependency('libssl.so', '=3-64'));
        $entityManager->persist($coreRepository);
        $entityManager->persist($systemd);
        $entityManager->persist($openssl);
        $entityManager->persist($openssl1);
        $entityManager->flush();

        $abstractRelationRepository = $entityManager->getRepository(AbstractRelation::class);
        $this->assertInstanceOf(AbstractRelationRepository::class, $abstractRelationRepository);
        $abstractRelationRepository->updateTargets();

        $entityManager->flush();
        $entityManager->clear();

        $packageRepository = $entityManager->getRepository(Package::class);

        $databaseSystemd = $packageRepository->find($systemd->getId());
        $this->assertInstanceOf(Package::class, $databaseSystemd);

        foreach ($databaseSystemd->getDependencies() as $dependency) {
            $this->assertInstanceOf(Package::class, $dependency->getTarget());
            $this->assertEquals($openssl->getId(), $dependency->getTarget()->getId());
        }
    }

    public function testProvisionsWithVersionsOperator(): void
    {
        $entityManager = $this->getEntityManager();

        $coreRepository = new Repository('core', Architecture::X86_64);
        $systemd = new Package(
            $coreRepository,
            'systemd',
            '251.7-4',
            Architecture::X86_64
        );
        $openssl = new Package(
            $coreRepository,
            'openssl',
            '3.0.7-2',
            Architecture::X86_64
        );
        $openssl
            ->addProvision(new Provision('libcrypto.so', '=3-64'))
            ->addProvision(new Provision('libssl.so', '=3-64'));
        $systemd
            ->addDependency(new Dependency('openssl'))
            ->addDependency(new Dependency('libcrypto.so', '>=3-64'))
            ->addDependency(new Dependency('libssl.so', '>=3-64'));
        $entityManager->persist($coreRepository);
        $entityManager->persist($systemd);
        $entityManager->persist($openssl);
        $entityManager->flush();

        $abstractRelationRepository = $entityManager->getRepository(AbstractRelation::class);
        $this->assertInstanceOf(AbstractRelationRepository::class, $abstractRelationRepository);
        $abstractRelationRepository->updateTargets();

        $entityManager->flush();
        $entityManager->clear();

        $packageRepository = $entityManager->getRepository(Package::class);

        $databaseSystemd = $packageRepository->find($systemd->getId());
        $this->assertInstanceOf(Package::class, $databaseSystemd);

        foreach ($databaseSystemd->getDependencies() as $dependency) {
            $this->assertInstanceOf(Package::class, $dependency->getTarget());
            $this->assertEquals($openssl->getId(), $dependency->getTarget()->getId());
        }
    }

    public function testDependencyWithVersionsOperator(): void
    {
        $entityManager = $this->getEntityManager();

        $coreRepository = new Repository('core', Architecture::X86_64);
        $systemd = new Package(
            $coreRepository,
            'systemd',
            '251.7-4',
            Architecture::X86_64
        );
        $openssl = new Package(
            $coreRepository,
            'openssl',
            '3.0.7-2',
            Architecture::X86_64
        );
        $systemd
            ->addDependency(new Dependency('openssl', '>=3'));
        $entityManager->persist($coreRepository);
        $entityManager->persist($systemd);
        $entityManager->persist($openssl);
        $entityManager->flush();

        $abstractRelationRepository = $entityManager->getRepository(AbstractRelation::class);
        $this->assertInstanceOf(AbstractRelationRepository::class, $abstractRelationRepository);
        $abstractRelationRepository->updateTargets();

        $entityManager->flush();
        $entityManager->clear();

        $packageRepository = $entityManager->getRepository(Package::class);

        $databaseSystemd = $packageRepository->find($systemd->getId());
        $this->assertInstanceOf(Package::class, $databaseSystemd);

        foreach ($databaseSystemd->getDependencies() as $dependency) {
            $this->assertInstanceOf(Package::class, $dependency->getTarget());
            $this->assertEquals($openssl->getId(), $dependency->getTarget()->getId());
        }
    }

    public function testDependencyWithNonmatchingVersionsOperator(): void
    {
        $entityManager = $this->getEntityManager();

        $coreRepository = new Repository('core', Architecture::X86_64);
        $systemd = new Package(
            $coreRepository,
            'systemd',
            '251.7-4',
            Architecture::X86_64
        );
        $openssl = new Package(
            $coreRepository,
            'openssl',
            '3.0.7-2',
            Architecture::X86_64
        );
        $systemd
            ->addDependency(new Dependency('openssl', '<3'));
        $entityManager->persist($coreRepository);
        $entityManager->persist($systemd);
        $entityManager->persist($openssl);
        $entityManager->flush();

        $abstractRelationRepository = $entityManager->getRepository(AbstractRelation::class);
        $this->assertInstanceOf(AbstractRelationRepository::class, $abstractRelationRepository);
        $abstractRelationRepository->updateTargets();

        $entityManager->flush();
        $entityManager->clear();

        $packageRepository = $entityManager->getRepository(Package::class);

        $databaseSystemd = $packageRepository->find($systemd->getId());
        $this->assertInstanceOf(Package::class, $databaseSystemd);

        foreach ($databaseSystemd->getDependencies() as $dependency) {
            $this->assertEquals('openssl', $dependency->getTargetName());
            $this->assertEquals('<3', $dependency->getTargetVersion());
            $this->assertNull($dependency->getTarget());
        }
    }
}
