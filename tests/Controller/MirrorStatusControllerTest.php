<?php

namespace App\Tests\Controller;

use App\Entity\Mirror;
use SymfonyDatabaseTest\DatabaseTestCase;

/**
 * @covers \App\Controller\MirrorStatusController
 */
class MirrorStatusControllerTest extends DatabaseTestCase
{
    public function testIndexAction()
    {
        $client = $this->getClient();
        $client->request('GET', '/mirrors');

        $this->assertTrue($client->getResponse()->isSuccessful());
    }

    public function testDatatablesAction()
    {
        $entityManager = $this->getEntityManager();
        $mirror = new Mirror('https://127.0.0.2/', 'https');
        $entityManager->persist($mirror);
        $entityManager->flush();

        $client = $this->getClient();
        $client->request('GET', '/mirrors/datatables');
        $response = $client->getResponse();

        $this->assertTrue($response->isSuccessful());
        $this->assertJson($response->getContent());
        $jsonArray = json_decode($response->getContent(), true);
        $this->assertCount(1, $jsonArray['data']);
        $this->assertEquals('https://127.0.0.2/', $jsonArray['data'][0]['url']);
        $this->assertEquals('https', $jsonArray['data'][0]['protocol']);
    }

    public function testEmptyDatatablesAction()
    {
        $entityManager = $this->getEntityManager();
        $mirror = new Mirror('http://127.0.0.2/', 'http');
        $entityManager->persist($mirror);
        $entityManager->flush();

        $client = $this->getClient();
        $client->request('GET', '/mirrors/datatables');
        $response = $client->getResponse();

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertJson($response->getContent());
        $jsonArray = json_decode($response->getContent(), true);
        $this->assertCount(0, $jsonArray['data']);
    }
}
