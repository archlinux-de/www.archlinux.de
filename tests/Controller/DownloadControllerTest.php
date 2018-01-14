<?php

namespace App\Tests\Controller;

use App\Entity\Mirror;
use App\Entity\Release;
use App\Entity\Torrent;
use App\Tests\Util\DatabaseTestCase;

/**
 * @covers \App\Controller\DownloadController
 */
class DownloadControllerTest extends DatabaseTestCase
{
    public function testDownloadButton()
    {
        $entityManager = $this->getEntityManager();

        $release = (new Release('2018.01.01'))
            ->setAvailable(true)
            ->setInfo('')
            ->setIsoUrl('')
            ->setCreated(new \DateTime())
            ->setReleaseDate(new \DateTime())
            ->setTorrent(
                (new Torrent())
                    ->setFileLength(1)
                    ->setFileName('archlinux-2018.01.01-x86_64.iso')
            );
        $entityManager->persist($release);

        $mirror = new Mirror('', 'https');
        $entityManager->persist($mirror);

        $entityManager->flush();
        $entityManager->clear();

        $client = $this->getClient();

        $crawler = $client->request('GET', '/download');
        $primaryButtons = $crawler->filter('.btn-primary');
        $this->assertEquals(1, $primaryButtons->count());
        $this->assertContains(
            'archlinux-2018.01.01-x86_64.iso',
            $primaryButtons->getNode(0)->getAttribute('href')
        );
    }
}
