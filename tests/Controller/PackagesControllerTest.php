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
    public function testDrawIsReturnedCorrectly(): void
    {
        $client = $this->getClient();

        $client->request('GET', '/packages/datatables', ['draw' => 42, 'length' => 1]);

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertIsString($client->getResponse()->getContent());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(42, $responseData['draw']);
    }

    public function testIndexAction(): void
    {
        $client = $this->getClient();

        $client->request('GET', '/packages', ['search' => 'foo', 'repository' => 'bar']);

        $this->assertTrue($client->getResponse()->isSuccessful());
    }

    public function testOrderByName(): void
    {
        $entityManager = $this->getEntityManager();

        $coreRepository = new Repository('core', Architecture::X86_64);
        $php = new Package(
            $coreRepository,
            'php',
            '7.3.1-1',
            Architecture::X86_64
        );
        $pacman = new Package(
            $coreRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        );
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

    public function testSearch(): void
    {
        $entityManager = $this->getEntityManager();

        $coreRepository = new Repository('core', Architecture::X86_64);
        $php = new Package(
            $coreRepository,
            'php',
            '7.3.1-1',
            Architecture::X86_64
        );
        $pacman = new Package(
            $coreRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        );
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

    public function testFilterByName(): void
    {
        $entityManager = $this->getEntityManager();

        $coreRepository = new Repository('core', Architecture::X86_64);
        $php = new Package(
            $coreRepository,
            'php',
            '7.3.1-1',
            Architecture::X86_64
        );
        $pacman = new Package(
            $coreRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        );
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

    public function testEmptyRequest(): void
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

    public function testOpenSearchAction(): void
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

    public function testFeedAction(): void
    {
        $entityManager = $this->getEntityManager();

        $coreRepository = new Repository('core', Architecture::X86_64);
        $php = new Package(
            $coreRepository,
            'php',
            '7.3.1-1',
            Architecture::X86_64
        );
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
        $this->assertEquals($php->getName() . ' ' . $php->getVersion(), (string)$xml->entry->title);
        $this->assertEquals($php->getDescription(), (string)$xml->entry->content);
        $this->assertNotNull($xml->entry->link->attributes());
        $this->assertEquals(
            'http://localhost/packages/core/x86_64/php',
            (string)$xml->entry->link->attributes()->href
        );
    }

    public function testEmptySuggest(): void
    {
        $client = $this->getClient();

        $client->request('GET', '/packages/suggest', ['term' => '']);

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertIsString($client->getResponse()->getContent());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(0, $responseData);
    }

    public function testSuggest(): void
    {
        $entityManager = $this->getEntityManager();

        $coreRepository = new Repository('core', Architecture::X86_64);
        $php = new Package(
            $coreRepository,
            'php',
            '7.3.1-1',
            Architecture::X86_64
        );
        $pacman = new Package(
            $coreRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        );
        $entityManager->persist($coreRepository);
        $entityManager->persist($php);
        $entityManager->persist($pacman);
        $entityManager->flush();

        $client = $this->getClient();

        $client->request('GET', '/packages/suggest', ['term' => 'pac']);

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertIsString($client->getResponse()->getContent());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $responseData);
        $this->assertEquals('pacman', $responseData[0]);
    }
}
