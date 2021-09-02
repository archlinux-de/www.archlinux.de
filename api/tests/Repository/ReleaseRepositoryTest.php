<?php

namespace App\Tests\Repository;

use App\Entity\Release;
use App\Repository\ReleaseRepository;
use SymfonyDatabaseTest\DatabaseTestCase;

class ReleaseRepositoryTest extends DatabaseTestCase
{
    public function testGetLatestAvailable(): void
    {
        $release = new Release('2018-01-01');
        $release->setAvailable(true);
        $release->setInfo('');
        $release->setCreated(new \DateTime('2018-01-01'));
        $release->setReleaseDate(new \DateTime('2018-01-01'));

        $entityManager = $this->getEntityManager();
        $entityManager->persist($release);
        $entityManager->flush();
        $entityManager->clear();

        /** @var ReleaseRepository $releaseRepository */
        $releaseRepository = $this->getRepository(Release::class);
        $latestRelease = $releaseRepository->getLatestAvailable();
        $this->assertEquals($release->getVersion(), $latestRelease->getVersion());
    }

    public function testGetAvailableByVersion(): void
    {
        $release = new Release('2018-01-01');
        $release->setAvailable(true);
        $release->setInfo('');
        $release->setCreated(new \DateTime('2018-01-01'));
        $release->setReleaseDate(new \DateTime('2018-01-01'));

        $entityManager = $this->getEntityManager();
        $entityManager->persist($release);
        $entityManager->flush();
        $entityManager->clear();

        /** @var ReleaseRepository $releaseRepository */
        $releaseRepository = $this->getRepository(Release::class);
        $latestRelease = $releaseRepository->getAvailableByVersion($release->getVersion());
        $this->assertEquals($release->getVersion(), $latestRelease->getVersion());
    }

    public function testFindAllExceptByVersions(): void
    {
        $releaseA = new Release('A');
        $releaseA->setAvailable(true);
        $releaseA->setInfo('');
        $releaseA->setCreated(new \DateTime('2018-01-01'));
        $releaseA->setReleaseDate(new \DateTime('2018-01-01'));

        $releaseB = new Release('B');
        $releaseB->setAvailable(true);
        $releaseB->setInfo('');
        $releaseB->setCreated(new \DateTime('2018-01-01'));
        $releaseB->setReleaseDate(new \DateTime('2018-01-01'));

        $entityManager = $this->getEntityManager();
        $entityManager->persist($releaseA);
        $entityManager->persist($releaseB);
        $entityManager->flush();

        /** @var ReleaseRepository $releaseRepository */
        $releaseRepository = $this->getRepository(Release::class);
        $releases = $releaseRepository->findAllExceptByVersions(['A']);

        $this->assertCount(1, $releases);
        $this->assertEquals('B', $releases[0]->getVersion());
    }

    public function testGetSize(): void
    {
        $releaseA = new Release('A');
        $releaseA->setAvailable(true);
        $releaseA->setInfo('');
        $releaseA->setCreated(new \DateTime('2018-01-01'));
        $releaseA->setReleaseDate(new \DateTime('2018-01-01'));

        $releaseB = new Release('B');
        $releaseB->setAvailable(true);
        $releaseB->setInfo('');
        $releaseB->setCreated(new \DateTime('2018-01-01'));
        $releaseB->setReleaseDate(new \DateTime('2018-01-01'));

        $entityManager = $this->getEntityManager();
        $entityManager->persist($releaseA);
        $entityManager->persist($releaseB);
        $entityManager->flush();
        $entityManager->clear();

        /** @var ReleaseRepository $releaseRepository */
        $releaseRepository = $this->getRepository(Release::class);
        $this->assertEquals(2, $releaseRepository->getSize());
    }

    public function testFindAllAvailable(): void
    {
        $release = new Release('2018-01-01');
        $release->setAvailable(true);
        $release->setInfo('');
        $release->setCreated(new \DateTime('2018-01-01'));
        $release->setReleaseDate(new \DateTime('2018-01-01'));

        $entityManager = $this->getEntityManager();
        $entityManager->persist($release);
        $entityManager->flush();
        $entityManager->clear();

        /** @var ReleaseRepository $releaseRepository */
        $releaseRepository = $this->getRepository(Release::class);
        $releases = $releaseRepository->findAllAvailable();
        $this->assertCount(1, $releases);
        $this->assertEquals($release->getVersion(), $releases[0]->getVersion());
    }
}
