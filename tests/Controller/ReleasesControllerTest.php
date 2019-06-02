<?php

namespace App\Tests\Controller;

use App\Entity\Release;
use App\Entity\Torrent;
use App\Tests\Util\DatabaseTestCase;

/**
 * @covers \App\Controller\ReleasesController
 */
class ReleasesControllerTest extends DatabaseTestCase
{
    public function testIndexAction()
    {
        $client = $this->getClient();

        $client->request('GET', '/releases', ['search' => 'foo']);

        $this->assertTrue($client->getResponse()->isSuccessful());
    }

    public function testItemAction()
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
        $this->assertStringContainsString('release.iso', $client->getResponse()->getContent());
    }

    public function testDatatablesAction()
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
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $responseData['data']);
        $this->assertEquals('2018.01.01', $responseData['data'][0]['version']);
    }

    public function testFeedAction()
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
        $response = $client->getResponse()->getContent();
        $xml = \simplexml_load_string($response);
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
