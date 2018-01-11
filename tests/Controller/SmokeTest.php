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
 * @coversNothing
 */
class SmokeTest extends DatabaseTestCase
{
    public function setUp()
    {
        parent::setUp();
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

        $release = (new Release('2018.01.01'))
            ->setAvailable(true)
            ->setInfo('')
            ->setIsoUrl('')
            ->setCreated(new \DateTime())
            ->setReleaseDate(new \DateTime())
            ->setTorrent(
                (new Torrent())->setFileLength(1)
            );
        $entityManager->persist($release);

        $mirror = new Mirror('', 'https');
        $entityManager->persist($mirror);

        $entityManager->flush();
        $entityManager->clear();
    }

    /**
     * @param string $url
     * @dataProvider provideUrls
     */
    public function testRequestIsSuccessful(string $url)
    {
        $client = $this->getClient();

        $client->request('GET', $url);

        $this->assertTrue($client->getResponse()->isSuccessful());
    }

    /**
     * @param string $url
     * @dataProvider provideRedirectUrls
     */
    public function testRequestIsRedirect(string $url)
    {
        $client = $this->getClient();

        $client->request('GET', $url);

        $this->assertTrue($client->getResponse()->isRedirection());
    }

    public function testUnknownUrlFails()
    {
        $client = $this->getClient();

        $client->request('GET', '/unknown');

        $this->assertTrue($client->getResponse()->isNotFound());
    }

    /**
     * @return array
     */
    public function provideUrls(): array
    {
        return [
            ['/packages/opensearch'],
            ['/'],
            ['/mirrors'],
            ['/packages/core/x86_64/pacman'],
            ['/packages'],
            ['/packages/suggest?term=foo'],
            ['/packages/feed'],
            ['/download'],
            ['/packages/datatables?draw=1&length=1'],
            ['/sitemap.xml'],
            ['/news/feed'],
            ['/impressum'],
            ['/privacy-policy'],
            ['/mirrors/datatables']
        ];
    }

    /**
     * @return array
     */
    public function provideRedirectUrls(): array
    {
        return [
            ['/download/foo'],
            ['/download/iso/2018.01.01/archlinux-2018.01.01-x86_64.iso'],
            ['/download/core/os/x86_64/pacman-5.0.2-2-x86_64.pkg.tar.xz']
        ];
    }
}
