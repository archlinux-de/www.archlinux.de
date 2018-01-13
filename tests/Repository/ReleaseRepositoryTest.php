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
}
