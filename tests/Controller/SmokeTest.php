<?php

namespace App\Tests\Controller;

use App\Entity\Mirror;
use App\Entity\NewsAuthor;
use App\Entity\NewsItem;
use App\Entity\Packages\Architecture;
use App\Entity\Packages\Package;
use App\Entity\Packages\Repository;
use App\Entity\Release;
use App\Entity\Torrent;
use App\Tests\DatabaseSearchTestCase;

/**
 * @coversNothing
 */
class SmokeTest extends DatabaseSearchTestCase
{
    public function setUp(): void
    {
        parent::setUp();
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

        $release = (new Release('2018.01.01'))
            ->setAvailable(true)
            ->setInfo('')
            ->setIsoUrl('')
            ->setCreated(new \DateTime('2018-01-01'))
            ->setReleaseDate(new \DateTime('2018-01-01'))
            ->setTorrent(
                (new Torrent())->setFileLength(1)->setFileName('release.iso')
            );
        $entityManager->persist($release);

        $mirror = (new Mirror('https://127.0.0.2/', 'https'))
            ->setScore(1)
            ->setLastSync(new \DateTime('2020-01-01'));
        $entityManager->persist($mirror);

        $newsItem = (new NewsItem(1))
            ->setTitle('Big News')
            ->setLink('https://www.archlinux.de/')
            ->setDescription('Foo bar')
            ->setLastModified(new \DateTime('2018-01-01'))
            ->setAuthor((new NewsAuthor())->setName('Bob'));
        $entityManager->persist($newsItem);

        $entityManager->flush();
        $entityManager->clear();
    }

    /**
     * @param string $url
     * @dataProvider provideUrls
     */
    public function testRequestIsSuccessful(string $url): void
    {
        $client = $this->getClient();

        $client->request('GET', $url);

        $this->assertTrue($client->getResponse()->isSuccessful());
    }

    /**
     * @param string $url
     * @dataProvider provideRedirectUrls
     */
    public function testRequestIsRedirect(string $url): void
    {
        $client = $this->getClient();

        $client->request('GET', $url);

        $this->assertTrue($client->getResponse()->isRedirection());
    }

    public function testUnknownUrlFails(): void
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
            ['/sitemap.xml'],
            ['/news'],
            ['/news/feed'],
            ['/news/1-Big-News'],
            ['/releases'],
            ['/releases/2018.01.01'],
            ['/releases/feed'],
            ['/impressum'],
            ['/privacy-policy']
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
            ['/download/core/os/x86_64/pacman-5.0.2-2-x86_64.pkg.tar.xz'],
            ['/download/core/os/x86_64/pacman-6.0.0-1-x86_64.pkg.tar.zst']
        ];
    }
}
