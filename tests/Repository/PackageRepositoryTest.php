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
}
