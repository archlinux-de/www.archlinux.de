<?php

namespace App\Tests\Controller;

use App\Entity\Release;
use App\Entity\Torrent;
use SymfonyDatabaseTest\DatabaseTestCase;

/**
 * @covers \App\Controller\ReleasesController
 */
class ReleasesControllerTest extends DatabaseTestCase
{
    public function testFeedAction(): void
    {
        $entityManager = $this->getEntityManager();
        $release = (new Release('2018.01.01'))
            ->setAvailable(true)
            ->setInfo('')
            ->setIsoUrl('')
            ->setCreated(new \DateTime())
            ->setReleaseDate(new \DateTime())
            ->setTorrent(
                (new Torrent())->setFileLength(1)->setFileName('release.iso')
            );
        $entityManager->persist($release);
        $entityManager->flush();

        $client = $this->getClient();
        $client->request('GET', '/releases/feed');

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertStringStartsWith(
            'application/atom+xml; charset=UTF-8',
            (string)$client->getResponse()->headers->get('Content-Type')
        );
        $this->assertEquals('UTF-8', $client->getResponse()->getCharset());
        $xml = \simplexml_load_string((string)($client->getResponse()->getContent()));
        $this->assertNotFalse($xml);
        $this->assertEmpty(\libxml_get_errors());
        $this->assertEquals($release->getVersion(), (string)$xml->entry->title);
        $this->assertEquals($release->getInfo(), (string)$xml->entry->content);
        $this->assertNotNull($xml->entry->link->attributes());
        $this->assertStringContainsString(
            $release->getVersion(),
            (string)$xml->entry->link->attributes()->href
        );
    }

    public function testReleasesAction(): void
    {
        $entityManager = $this->getEntityManager();
        $release = (new Release('2018.01.01'))
            ->setAvailable(true)
            ->setInfo('')
            ->setIsoUrl('')
            ->setCreated(new \DateTime())
            ->setReleaseDate(new \DateTime())
            ->setTorrent(
                (new Torrent())->setFileLength(1)->setFileName('release.iso')
            );
        $entityManager->persist($release);
        $entityManager->flush();

        $client = $this->getClient();

        $client->request('GET', '/api/releases', ['query' => '2018']);

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertIsString($client->getResponse()->getContent());
        $this->assertJson($client->getResponse()->getContent());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $responseData['items']);
        $this->assertEquals('2018.01.01', $responseData['items'][0]['version']);
    }

    public function testReleaseAction(): void
    {
        $entityManager = $this->getEntityManager();
        $release = (new Release('2018.01.01'))
            ->setAvailable(true)
            ->setInfo('info')
            ->setIsoUrl('http://localhost/iso')
            ->setCreated(new \DateTime())
            ->setReleaseDate(new \DateTime('2018-01-01'))
            ->setSha1Sum('abcdef')
            ->setTorrent(
                (new Torrent())
                    ->setFileLength(1)
                    ->setFileName('release.iso')
                    ->setUrl('http://localhost/torrent')
                    ->setMagnetUri('magnet://localhost/torrent')
            );
        $entityManager->persist($release);
        $entityManager->flush();

        $client = $this->getClient();

        $client->request('GET', '/api/releases/2018.01.01');

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertIsString($client->getResponse()->getContent());
        $this->assertJson($client->getResponse()->getContent());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(
            [
                'version' => '2018.01.01',
                'kernelVersion' => null,
                'releaseDate' => '2018-01-01T00:00:00+00:00',
                'available' => true,
                'info' => 'info',
                'isoUrl' => 'http://localhost/download/iso/2018.01.01/release.iso',
                'sha1Sum' => 'abcdef',
                'torrentUrl' => 'https://www.archlinux.orghttp://localhost/torrent',
                'fileSize' => 1,
                'magnetUri' => 'magnet://localhost/torrent',
                'isoPath' => 'http://localhost/iso',
                'isoSigUrl' => 'https://www.archlinux.orghttp://localhost/iso.sig',
                'fileName' => 'release.iso'
            ],
            $responseData
        );
    }
}
