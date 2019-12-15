<?php

namespace App\Tests\Controller;

use App\Entity\Packages\Architecture;
use App\Entity\Packages\Package;
use App\Entity\Packages\Packager;
use App\Entity\Packages\Repository;
use SymfonyDatabaseTest\DatabaseTestCase;

/**
 * @covers \App\Controller\PackagesController
 */
class PackagesControllerTest extends DatabaseTestCase
{
    public function testDrawIsReturnedCorrectly()
    {
        $client = $this->getClient();

        $client->request('GET', '/packages/datatables', ['draw' => 42, 'length' => 1]);

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertIsString($client->getResponse()->getContent());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(42, $responseData['draw']);
    }

    public function testIndexAction()
    {
        $client = $this->getClient();

        $client->request('GET', '/packages', ['search' => 'foo', 'repository' => 'bar']);

        $this->assertTrue($client->getResponse()->isSuccessful());
    }

    public function testOrderByName()
    {
        $entityManager = $this->getEntityManager();

        $coreRepository = new Repository('core', Architecture::X86_64);
        $php = (new Package(
            $coreRepository,
            'php',
            '7.3.1-1',
            Architecture::X86_64
        ))->setMTime(new \DateTime());
        $pacman = (new Package(
            $coreRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        ))->setMTime(new \DateTime());
        $entityManager->persist($coreRepository);
        $entityManager->persist($php);
        $entityManager->persist($pacman);
        $entityManager->flush();

        $client = $this->getClient();

        $client->request(
            'GET',
            '/packages/datatables',
            [
                'draw' => 1,
                'length' => 2,
                'columns' => [
                    [
                        'data' => 'name',
                        'name' => '',
                        'orderable' => true,
                        'search' => [
                            'regex' => false,
                            'value' => ''
                        ],
                        'searchable' => true
                    ]
                ],
                'order' => [
                    [
                        'column' => 0,
                        'dir' => 'asc'
                    ]
                ]
            ]
        );

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertIsString($client->getResponse()->getContent());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(2, $responseData['data']);
        $this->assertEquals('pacman', $responseData['data'][0]['name']);
        $this->assertEquals('php', $responseData['data'][1]['name']);
    }

    public function testSearch()
    {
        $entityManager = $this->getEntityManager();

        $coreRepository = new Repository('core', Architecture::X86_64);
        $php = (new Package(
            $coreRepository,
            'php',
            '7.3.1-1',
            Architecture::X86_64
        ))->setMTime(new \DateTime());
        $pacman = (new Package(
            $coreRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        ))->setMTime(new \DateTime());
        $entityManager->persist($coreRepository);
        $entityManager->persist($php);
        $entityManager->persist($pacman);
        $entityManager->flush();

        $client = $this->getClient();

        $client->request(
            'GET',
            '/packages/datatables',
            [
                'draw' => 1,
                'length' => 2,
                'columns' => [
                    [
                        'data' => 'name',
                        'name' => '',
                        'orderable' => true,
                        'search' => [
                            'regex' => false,
                            'value' => ''
                        ],
                        'searchable' => true
                    ]
                ],
                'search' => [
                    'regex' => false,
                    'value' => 'pac'
                ]
            ]
        );

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertIsString($client->getResponse()->getContent());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $responseData['data']);
        $this->assertEquals('pacman', $responseData['data'][0]['name']);
    }

    public function testFilterByName()
    {
        $entityManager = $this->getEntityManager();

        $coreRepository = new Repository('core', Architecture::X86_64);
        $php = (new Package(
            $coreRepository,
            'php',
            '7.3.1-1',
            Architecture::X86_64
        ))->setMTime(new \DateTime());
        $pacman = (new Package(
            $coreRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        ))->setMTime(new \DateTime());
        $entityManager->persist($coreRepository);
        $entityManager->persist($php);
        $entityManager->persist($pacman);
        $entityManager->flush();

        $client = $this->getClient();

        $client->request(
            'GET',
            '/packages/datatables',
            [
                'draw' => 1,
                'length' => 2,
                'columns' => [
                    [
                        'data' => 'name',
                        'name' => '',
                        'orderable' => true,
                        'search' => [
                            'regex' => false,
                            'value' => 'pac'
                        ],
                        'searchable' => true
                    ]
                ]
            ]
        );

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertIsString($client->getResponse()->getContent());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $responseData['data']);
        $this->assertEquals('pacman', $responseData['data'][0]['name']);
    }

    public function testEmptyRequest()
    {
        $client = $this->getClient();

        $client->request('GET', '/packages/datatables', ['draw' => 1, 'length' => 1]);

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertIsString($client->getResponse()->getContent());
        $this->assertJson($client->getResponse()->getContent());
        $this->assertStringContainsString(
            'application/json',
            (string)$client->getResponse()->headers->get('Content-Type')
        );

        $responseData = json_decode($client->getResponse()->getContent(), true);
        foreach (['draw', 'recordsTotal', 'recordsFiltered'] as $metaData) {
            $this->assertArrayHasKey($metaData, $responseData);
            $this->assertIsInt($responseData[$metaData]);
        }
        $this->assertArrayHasKey('data', $responseData);
        $this->assertIsArray($responseData['data']);
    }

    public function testOpenSearchAction()
    {
        $client = static::createClient();

        $client->request('GET', '/packages/opensearch');

        $this->assertTrue($client->getResponse()->isSuccessful());
        $response = $client->getResponse()->getContent();
        $this->assertIsString($response);
        $this->assertNotFalse(\simplexml_load_string($response));
        $this->assertEmpty(\libxml_get_errors());
        $this->assertStringContainsString('{searchTerms}', $response);
    }

    public function testFeedAction()
    {
        $entityManager = $this->getEntityManager();

        $coreRepository = new Repository('core', Architecture::X86_64);
        $php = (new Package(
            $coreRepository,
            'php',
            '7.3.1-1',
            Architecture::X86_64
        ))->setMTime(new \DateTime());
        $php->setPackager(new Packager('', ''));
        $entityManager->persist($coreRepository);
        $entityManager->persist($php);
        $entityManager->flush();

        $client = $this->getClient();

        $client->request('GET', '/packages/feed');

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertStringStartsWith(
            'application/atom+xml; charset=UTF-8',
            (string)$client->getResponse()->headers->get('Content-Type')
        );
        $this->assertEquals('UTF-8', $client->getResponse()->getCharset());
        $xml = \simplexml_load_string((string)($client->getResponse()->getContent()));
        $this->assertNotFalse($xml);
        $this->assertEmpty(\libxml_get_errors());
        $this->assertEquals($php->getName() . ' ' . $php->getVersion(), $xml->entry->title->__toString());
        $this->assertEquals($php->getDescription(), $xml->entry->content->__toString());
        $this->assertNotNull($xml->entry->link->attributes());
        $this->assertEquals(
            'http://localhost/packages/core/x86_64/php',
            $xml->entry->link->attributes()->href->__toString()
        );
    }
}
