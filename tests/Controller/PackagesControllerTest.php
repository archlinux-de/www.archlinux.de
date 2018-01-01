<?php

namespace App\Tests\Controller;

use App\Tests\Util\DatabaseTestCase;

/**
 * @coversNothing
 */
class PackagesControllerTest extends DatabaseTestCase
{
    public function testEmptyRequest()
    {
        $client = $this->getClient();

        $client->request('GET', '/packages/datatables', ['draw' => 1, 'length' => 1]);

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertJson($client->getResponse()->getContent());
        $this->assertContains('application/json', $client->getResponse()->headers->get('Content-Type'));

        $responseData = json_decode($client->getResponse()->getContent(), true);
        foreach (['draw', 'recordsTotal', 'recordsFiltered'] as $metaData) {
            $this->assertArrayHasKey($metaData, $responseData);
            $this->assertInternalType('int', $responseData[$metaData]);
        }
        $this->assertArrayHasKey('data', $responseData);
        $this->assertInternalType('array', $responseData['data']);
    }

    public function testDrawIsReturnedCorrectly()
    {
        $client = $this->getClient();

        $client->request('GET', '/packages/datatables', ['draw' => 42, 'length' => 1]);

        $this->assertTrue($client->getResponse()->isSuccessful());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(42, $responseData['draw']);
    }
}
