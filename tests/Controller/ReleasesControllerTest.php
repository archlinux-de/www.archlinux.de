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
    public function testIndexAction(): void
    {
        $client = $this->getClient();

        $client->request('GET', '/releases', ['search' => 'foo']);

        $this->assertTrue($client->getResponse()->isSuccessful());
    }

    public function testItemAction(): void
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

        $crawler = $client->request('GET', '/releases/2018.01.01');

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertStringContainsString('2018.01.01', $crawler->filter('h1')->text());
        $this->assertIsString($client->getResponse()->getContent());
        $this->assertStringContainsString('release.iso', $client->getResponse()->getContent());
    }

    public function testDatatablesAction(): void
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

        $client->request(
            'GET',
            '/releases/datatables',
            [
                'draw' => 1,
                'length' => 2,
                'columns' => [
                    [
                        'data' => 'version',
                        'name' => '',
                        'orderable' => false,
                        'search' => [
                            'regex' => false,
                            'value' => ''
                        ],
                        'searchable' => true
                    ]
                ],
                'search' => [
                    'regex' => false,
                    'value' => '2018'
                ]
            ]
        );

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertIsString($client->getResponse()->getContent());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $responseData['data']);
        $this->assertEquals('2018.01.01', $responseData['data'][0]['version']);
    }

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
        $this->assertEquals($release->getVersion(), $xml->entry->title->__toString());
        $this->assertEquals($release->getInfo(), $xml->entry->content->__toString());
        $this->assertNotNull($xml->entry->link->attributes());
        $this->assertStringContainsString(
            $release->getVersion(),
            $xml->entry->link->attributes()->href->__toString()
        );
    }
}
