<?php

namespace App\Tests\Controller;

use App\Entity\Country;
use App\Entity\Mirror;
use App\Entity\Packages\Architecture;
use App\Entity\Packages\Package;
use App\Entity\Packages\Repository;
use App\Entity\Release;
use App\Entity\Torrent;
use App\Tests\DatabaseSearchTestCase;

/**
 * @covers \App\Controller\MirrorController
 */
class MirrorControllerTest extends DatabaseSearchTestCase
{
    public function testIsoAction(): void
    {
        $entityManager = $this->getEntityManager();
        $country = (new Country('de'))->setName('Germany');

        $mirror = new Mirror('https://127.0.0.2/', 'https');
        $mirror->setActive(true);
        $mirror->setIsos(true);
        $mirror->setScore(1);
        $mirror->setLastSync(new \DateTime());
        $mirror->setCountry($country);

        $release = (new Release('2018.01.01'))
            ->setAvailable(true)
            ->setInfo('')
            ->setIsoUrl('')
            ->setCreated(new \DateTime())
            ->setReleaseDate(new \DateTime())
            ->setTorrent(
                (new Torrent())->setFileLength(1)
            );

        $entityManager->persist($country);
        $entityManager->persist($mirror);
        $entityManager->persist($release);
        $entityManager->flush();

        sleep(1);

        $filePath = 'iso/2018.01.01/archlinux-2018.01.01-x86_64.iso';
        $client = $this->getClient();

        $client->request('GET', '/download/' . $filePath);
        $this->assertTrue($client->getResponse()->isRedirect('https://127.0.0.2/' . $filePath));
    }

    public function testFailIfIsoIsUnkown(): void
    {
        $entityManager = $this->getEntityManager();
        $mirror = new Mirror('https://127.0.0.2/', 'https');
        $mirror->setActive(true);
        $mirror->setIsos(true);
        $mirror->setScore(1);
        $entityManager->persist($mirror);
        $entityManager->flush();

        sleep(1);

        $filePath = 'iso/2018.01.01/archlinux-2018.01.01-x86_64.iso';
        $client = $this->getClient();

        $client->request('GET', '/download/' . $filePath);
        $this->assertTrue($client->getResponse()->isNotFound());
    }

    /**
     * @param string $packageExtension
     * @dataProvider providePackageExtensions
     */
    public function testPackageAction(string $packageExtension): void
    {
        $entityManager = $this->getEntityManager();
        $mirror = new Mirror('https://127.0.0.2/', 'https');
        $mirror->setActive(true);
        $mirror->setIsos(true);
        $mirror->setScore(1);
        $coreRepository = new Repository('core', Architecture::X86_64);
        $pacman = new Package(
            $coreRepository,
            'linux',
            '3.11-1',
            Architecture::X86_64
        );
        $entityManager->persist($mirror);
        $entityManager->persist($coreRepository);
        $entityManager->persist($pacman);
        $entityManager->flush();

        sleep(1);

        $filePath = 'core/os/x86_64/linux-3.11-1-x86_64.pkg.tar.' . $packageExtension;
        $client = $this->getClient();

        $client->request('GET', '/download/' . $filePath);
        $this->assertTrue($client->getResponse()->isRedirect('https://127.0.0.2/' . $filePath));
    }

    public function testPackageNotFoundAction(): void
    {
        $entityManager = $this->getEntityManager();
        $mirror = new Mirror('https://127.0.0.2/', 'https');
        $mirror->setActive(true);
        $mirror->setIsos(true);
        $mirror->setScore(1);
        $entityManager->persist($mirror);
        $entityManager->flush();

        sleep(1);

        $filePath = 'core/os/x86_64/linux-3.11-1-x86_64.pkg.tar.xz';
        $client = $this->getClient();

        $client->request('GET', '/download/' . $filePath);
        $this->assertTrue($client->getResponse()->isNotFound());
    }

    public function testInvalidPackageNotFoundAction(): void
    {
        $entityManager = $this->getEntityManager();
        $mirror = new Mirror('https://127.0.0.2/', 'https');
        $mirror->setActive(true);
        $mirror->setIsos(true);
        $mirror->setScore(1);
        $entityManager->persist($mirror);
        $entityManager->flush();

        sleep(1);

        $filePath = 'core/os/x86_64/linux-3.11-1-2-1-2-4-x86_64.pkg.tar.xz';
        $client = $this->getClient();

        $client->request('GET', '/download/' . $filePath);
        $this->assertTrue($client->getResponse()->isNotFound());
    }

    public function testFallbackAction(): void
    {
        $entityManager = $this->getEntityManager();
        $mirror = new Mirror('https://127.0.0.2/', 'https');
        $mirror->setActive(true);
        $mirror->setIsos(true);
        $mirror->setScore(1);
        $entityManager->persist($mirror);
        $entityManager->flush();

        sleep(1);

        $filePath = 'foo.txt';
        $client = $this->getClient();

        $client->request('GET', '/download/' . $filePath);
        $this->assertTrue($client->getResponse()->isRedirect('https://127.0.0.2/' . $filePath));
    }

    public function testMirrorNotFound(): void
    {
        $entityManager = $this->getEntityManager();
        $mirror = new Mirror('http://127.0.0.2/', 'http');
        $mirror->setActive(true);
        $mirror->setIsos(true);
        $mirror->setScore(1);
        $entityManager->persist($mirror);
        $entityManager->flush();

        sleep(1);

        $filePath = 'foo.txt';
        $client = $this->getClient();

        $client->request('GET', '/download/' . $filePath);
        $this->assertTrue($client->getResponse()->isNotFound());
    }

    /**
     * @return array<array>
     */
    public function providePackageExtensions(): array
    {
        return [
            ['gz'],
            ['xz'],
            ['zst'],
        ];
    }
}
