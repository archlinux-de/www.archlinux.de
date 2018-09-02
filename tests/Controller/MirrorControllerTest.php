<?php

namespace App\Tests\Controller;

use App\Entity\Mirror;
use App\Entity\Packages\Architecture;
use App\Entity\Packages\Package;
use App\Entity\Packages\Repository;
use App\Entity\Release;
use App\Entity\Torrent;
use App\Tests\Util\DatabaseTestCase;

/**
 * @covers \App\Controller\MirrorController
 */
class MirrorControllerTest extends DatabaseTestCase
{
    public function testIsoAction()
    {
        $entityManager = $this->getEntityManager();
        $mirror = new Mirror('https://127.0.0.2/', 'https');
        $mirror->setActive(true);
        $mirror->setIsos(true);
        $release = (new Release('2018.01.01'))
            ->setAvailable(true)
            ->setInfo('')
            ->setIsoUrl('')
            ->setCreated(new \DateTime())
            ->setReleaseDate(new \DateTime())
            ->setTorrent(
                (new Torrent())->setFileLength(1)
            );
        $entityManager->persist($mirror);
        $entityManager->persist($release);
        $entityManager->flush();

        $filePath = 'iso/2018.01.01/archlinux-2018.01.01-x86_64.iso';
        $client = $this->getClient();

        $client->request('GET', '/download/' . $filePath);
        $this->assertTrue($client->getResponse()->isRedirect('https://127.0.0.2/' . $filePath));
    }

    public function testFailIfIsoIsUnkown()
    {
        $entityManager = $this->getEntityManager();
        $mirror = new Mirror('https://127.0.0.2/', 'https');
        $mirror->setActive(true);
        $mirror->setIsos(true);
        $entityManager->persist($mirror);
        $entityManager->flush();

        $filePath = 'iso/2018.01.01/archlinux-2018.01.01-x86_64.iso';
        $client = $this->getClient();

        $client->request('GET', '/download/' . $filePath);
        $this->assertTrue($client->getResponse()->isNotFound());
    }

    public function testPackageAction()
    {
        $entityManager = $this->getEntityManager();
        $mirror = new Mirror('https://127.0.0.2/', 'https');
        $mirror->setActive(true);
        $mirror->setIsos(true);
        $coreRepository = new Repository('core', Architecture::X86_64);
        $pacman = (new Package(
            $coreRepository,
            'linux',
            '3.11-1',
            Architecture::X86_64
        ))->setMTime(new \DateTime());
        $entityManager->persist($mirror);
        $entityManager->persist($coreRepository);
        $entityManager->persist($pacman);
        $entityManager->flush();

        $filePath = 'core/os/x86_64/linux-3.11-1-x86_64.pkg.tar.xz';
        $client = $this->getClient();

        $client->request('GET', '/download/' . $filePath);
        $this->assertTrue($client->getResponse()->isRedirect('https://127.0.0.2/' . $filePath));
    }

    public function testPackageNotFoundAction()
    {
        $entityManager = $this->getEntityManager();
        $mirror = new Mirror('https://127.0.0.2/', 'https');
        $mirror->setActive(true);
        $mirror->setIsos(true);
        $entityManager->persist($mirror);
        $entityManager->flush();

        $filePath = 'core/os/x86_64/linux-3.11-1-x86_64.pkg.tar.xz';
        $client = $this->getClient();

        $client->request('GET', '/download/' . $filePath);
        $this->assertTrue($client->getResponse()->isNotFound());
    }

    public function testInvalidPackageNotFoundAction()
    {
        $entityManager = $this->getEntityManager();
        $mirror = new Mirror('https://127.0.0.2/', 'https');
        $mirror->setActive(true);
        $mirror->setIsos(true);
        $entityManager->persist($mirror);
        $entityManager->flush();

        $filePath = 'core/os/x86_64/linux-3.11-1-2-1-2-4-x86_64.pkg.tar.xz';
        $client = $this->getClient();

        $client->request('GET', '/download/' . $filePath);
        $this->assertTrue($client->getResponse()->isNotFound());
    }

    public function testFallbackAction()
    {
        $entityManager = $this->getEntityManager();
        $mirror = new Mirror('https://127.0.0.2/', 'https');
        $mirror->setActive(true);
        $mirror->setIsos(true);
        $entityManager->persist($mirror);
        $entityManager->flush();

        $filePath = 'foo.txt';
        $client = $this->getClient();

        $client->request('GET', '/download/' . $filePath);
        $this->assertTrue($client->getResponse()->isRedirect('https://127.0.0.2/' . $filePath));
    }

    public function testMirrorNotFound()
    {
        $entityManager = $this->getEntityManager();
        $mirror = new Mirror('http://127.0.0.2/', 'http');
        $mirror->setActive(true);
        $mirror->setIsos(true);
        $entityManager->persist($mirror);
        $entityManager->flush();

        $filePath = 'foo.txt';
        $client = $this->getClient();

        $client->request('GET', '/download/' . $filePath);
        $this->assertTrue($client->getResponse()->isNotFound());
    }
}
