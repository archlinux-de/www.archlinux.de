<?php

namespace App\Tests\Controller;

use App\Controller\ReleasesController;
use App\Entity\Release;
use App\Tests\DatabaseSearchTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ReleasesController::class)]
class ReleasesControllerTest extends DatabaseSearchTestCase
{
    public function testFeedAction(): void
    {
        $entityManager = $this->getEntityManager();
        $release = new Release('2018.01.01')
            ->setAvailable(true)
            ->setInfo('')
            ->setCreated(new \DateTime('2018-01-01'))
            ->setReleaseDate(new \DateTime('2018-01-01'))
            ->setFileLength(1)
            ->setFileName('release.iso');
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
            (string)$xml->entry->link->attributes()->{'href'}
        );
    }

    public function testReleasesAction(): void
    {
        $entityManager = $this->getEntityManager();
        $release = new Release('2018.01.01')
            ->setAvailable(true)
            ->setInfo('')
            ->setCreated(new \DateTime('2018-01-01'))
            ->setReleaseDate(new \DateTime('2018-01-01'))
            ->setFileLength(1)
            ->setFileName('release.iso');
        $entityManager->persist($release);
        $entityManager->flush();

        $client = $this->getClient();

        $client->request('GET', '/api/releases', ['query' => '2018']);

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertIsString($client->getResponse()->getContent());
        $this->assertJson($client->getResponse()->getContent());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertCount(1, $responseData['items']);
        $this->assertEquals('2018.01.01', $responseData['items'][0]['version']);
    }

    public function testReleaseAction(): void
    {
        $entityManager = $this->getEntityManager();
        $release = new Release('2018.01.01')
            ->setAvailable(true)
            ->setInfo('info')
            ->setCreated(new \DateTime('2018-01-01'))
            ->setReleaseDate(new \DateTime('2018-01-01'))
            ->setSha1Sum('abcdef')
            ->setFileLength(1)
            ->setFileName('release.iso')
            ->setTorrentUrl('/torrent')
            ->setMagnetUri('magnet://localhost/torrent');
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
                'sha256Sum' => null,
                'b2Sum' => null,
                'torrentUrl' => 'https://archlinux.org/torrent',
                'fileSize' => 1,
                'magnetUri' => 'magnet://localhost/torrent',
                'isoPath' => '/iso/2018.01.01/release.iso',
                'isoSigUrl' => 'http://localhost/download/iso/2018.01.01/release.iso.sig',
                'fileName' => 'release.iso',
                'directoryUrl' => 'http://localhost/download/iso/2018.01.01/'
            ],
            $responseData
        );
    }

    public function testReleasesActionWithQuotes(): void
    {
        $entityManager = $this->getEntityManager();

        $release1 = new Release('2023.01.01');
        $release1->setAvailable(true);
        $release1->setInfo('This is a major release with many new features.');
        $release1->setCreated(new \DateTime('2023-01-01'));
        $release1->setReleaseDate(new \DateTime('2023-01-01'));
        $release1->setFileLength(1);
        $release1->setFileName('release1.iso');

        $release2 = new Release('2023.01.02');
        $release2->setAvailable(true);
        $release2->setInfo('This is a minor release with some bug fixes.');
        $release2->setCreated(new \DateTime('2023-01-02'));
        $release2->setReleaseDate(new \DateTime('2023-01-02'));
        $release2->setFileLength(1);
        $release2->setFileName('release2.iso');

        $entityManager->persist($release1);
        $entityManager->persist($release2);
        $entityManager->flush();

        $client = $this->getClient();

        // Test quoted search for exact phrase in info
        $client->request('GET', '/api/releases', ['query' => '"major release"']);

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertIsString($client->getResponse()->getContent());
        $this->assertJson($client->getResponse()->getContent());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertCount(1, $responseData['items']);
        $this->assertEquals('2023.01.01', $responseData['items'][0]['version']);

        // Test unquoted search
        $client->request('GET', '/api/releases', ['query' => 'release']);
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertIsString($client->getResponse()->getContent());
        $this->assertJson($client->getResponse()->getContent());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertCount(2, $responseData['items']);
    }
}
