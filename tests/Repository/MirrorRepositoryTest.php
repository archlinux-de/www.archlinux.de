<?php

namespace App\Tests\Repository;

use App\Entity\Country;
use App\Entity\Mirror;
use App\Repository\MirrorRepository;
use SymfonyDatabaseTest\DatabaseTestCase;

class MirrorRepositoryTest extends DatabaseTestCase
{
    public function testFindBestByCountry()
    {
        $country = (new Country('de'))->setName('Germany');
        $lastSync = new \DateTime('2018-01-01');
        $mirror = new Mirror('https://downloads.archlinux.de', 'https');
        $mirror->setCountry($country);
        $mirror->setLastSync($lastSync);
        $mirror->setActive(true);
        $mirror->setIsos(true);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($country);
        $entityManager->persist($mirror);
        $entityManager->flush();
        $entityManager->clear();

        /** @var MirrorRepository $mirrorRepository */
        $mirrorRepository = $this->getRepository(Mirror::class);
        $mirrors = $mirrorRepository->findBestByCountryAndLastSync($country->getCode(), $lastSync);
        $this->assertCount(1, $mirrors);
        $this->assertEquals($mirror->getUrl(), $mirrors[0]->getUrl());
    }

    public function testFindBestLastSync()
    {
        $lastSync = new \DateTime('2018-01-01');
        $mirror = new Mirror('https://downloads.archlinux.de', 'https');
        $mirror->setActive(true);
        $mirror->setIsos(true);
        $mirror->setLastSync($lastSync);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($mirror);
        $entityManager->flush();
        $entityManager->clear();

        /** @var MirrorRepository $mirrorRepository */
        $mirrorRepository = $this->getRepository(Mirror::class);
        $mirrors = $mirrorRepository->findBestByCountryAndLastSync('us', $lastSync);
        $this->assertCount(1, $mirrors);
        $this->assertEquals($mirror->getUrl(), $mirrors[0]->getUrl());
    }

    public function testFindBestSecure()
    {
        $mirror = new Mirror('https://downloads.archlinux.de', 'https');
        $mirror->setActive(true);
        $mirror->setIsos(true);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($mirror);
        $entityManager->flush();
        $entityManager->clear();

        /** @var MirrorRepository $mirrorRepository */
        $mirrorRepository = $this->getRepository(Mirror::class);
        $mirrors = $mirrorRepository->findBestByCountryAndLastSync('us', new \DateTime());
        $this->assertCount(1, $mirrors);
        $this->assertEquals($mirror->getUrl(), $mirrors[0]->getUrl());
    }

    public function testFindSecure()
    {
        $country = (new Country('de'))->setName('Germany');
        $lastSync = new \DateTime('2018-01-01');
        $mirror = new Mirror('https://downloads.archlinux.de', 'https');
        $mirror->setActive(true);
        $mirror->setIsos(true);
        $mirror->setCountry($country);
        $mirror->setLastSync($lastSync);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($country);
        $entityManager->persist($mirror);
        $entityManager->flush();
        $entityManager->clear();

        /** @var MirrorRepository $mirrorRepository */
        $mirrorRepository = $this->getRepository(Mirror::class);
        $mirrors = $mirrorRepository->findSecure();
        $this->assertCount(1, $mirrors);
        $this->assertEquals($mirror->getUrl(), $mirrors[0]->getUrl());
    }

    public function testFindAllExceptByUrls()
    {
        $mirrorA = new Mirror('a', 'https');
        $mirrorB = new Mirror('b', 'https');
        $entityManager = $this->getEntityManager();
        $entityManager->persist($mirrorA);
        $entityManager->persist($mirrorB);
        $entityManager->flush();

        /** @var MirrorRepository $mirrorRepository */
        $mirrorRepository = $this->getRepository(Mirror::class);
        $mirrors = $mirrorRepository->findAllExceptByUrls(['a']);

        $this->assertCount(1, $mirrors);
        $this->assertEquals('b', $mirrors[0]->getUrl());
    }
}
