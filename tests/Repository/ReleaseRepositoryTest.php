<?php

namespace App\Tests\Repository;

use App\Entity\Release;
use App\Repository\ReleaseRepository;
use App\Tests\Util\DatabaseTestCase;

class ReleaseRepositoryTest extends DatabaseTestCase
{
    public function testGetLatestAvailable()
    {
        $release = new Release('2018-01-01');
        $release->setAvailable(true);
        $release->setInfo('');
        $release->setIsoUrl('');
        $release->setCreated(new \DateTime());
        $release->setReleaseDate(new \DateTime());

        $entityManager = $this->getEntityManager();
        $entityManager->persist($release);
        $entityManager->flush();
        $entityManager->clear();

        /** @var ReleaseRepository $releaseRepository */
        $releaseRepository = $this->getRepository(Release::class);
        $latestRelease = $releaseRepository->getLatestAvailable();
        $this->assertEquals($release->getVersion(), $latestRelease->getVersion());
    }

    public function testGetAvailableByVersion()
    {
        $release = new Release('2018-01-01');
        $release->setAvailable(true);
        $release->setInfo('');
        $release->setIsoUrl('');
        $release->setCreated(new \DateTime());
        $release->setReleaseDate(new \DateTime());

        $entityManager = $this->getEntityManager();
        $entityManager->persist($release);
        $entityManager->flush();
        $entityManager->clear();

        /** @var ReleaseRepository $releaseRepository */
        $releaseRepository = $this->getRepository(Release::class);
        $latestRelease = $releaseRepository->getAvailableByVersion($release->getVersion());
        $this->assertEquals($release->getVersion(), $latestRelease->getVersion());
    }

    public function testFindAllExceptByVersions()
    {
        $releaseA = new Release('A');
        $releaseA->setAvailable(true);
        $releaseA->setInfo('');
        $releaseA->setIsoUrl('');
        $releaseA->setCreated(new \DateTime());
        $releaseA->setReleaseDate(new \DateTime());

        $releaseB = new Release('B');
        $releaseB->setAvailable(true);
        $releaseB->setInfo('');
        $releaseB->setIsoUrl('');
        $releaseB->setCreated(new \DateTime());
        $releaseB->setReleaseDate(new \DateTime());

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

    public function testGetSize()
    {
        $releaseA = new Release('A');
        $releaseA->setAvailable(true);
        $releaseA->setInfo('');
        $releaseA->setIsoUrl('');
        $releaseA->setCreated(new \DateTime());
        $releaseA->setReleaseDate(new \DateTime());

        $releaseB = new Release('B');
        $releaseB->setAvailable(true);
        $releaseB->setInfo('');
        $releaseB->setIsoUrl('');
        $releaseB->setCreated(new \DateTime());
        $releaseB->setReleaseDate(new \DateTime());

        $entityManager = $this->getEntityManager();
        $entityManager->persist($releaseA);
        $entityManager->persist($releaseB);
        $entityManager->flush();
        $entityManager->clear();

        /** @var ReleaseRepository $releaseRepository */
        $releaseRepository = $this->getRepository(Release::class);
        $this->assertEquals(2, $releaseRepository->getSize());
    }

    public function testFindAllAvailable()
    {
        $release = new Release('2018-01-01');
        $release->setAvailable(true);
        $release->setInfo('');
        $release->setIsoUrl('');
        $release->setCreated(new \DateTime());
        $release->setReleaseDate(new \DateTime());

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
