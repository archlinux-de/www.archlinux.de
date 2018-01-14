<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GetOpenSearchTest extends WebTestCase
{
    public function testIndexAction()
    {
        $client = static::createClient();

        $client->request('GET', '/packages/opensearch');

        $response = $client->getResponse()->getContent();
        $this->assertNotFalse(\simplexml_load_string($response));
        $this->assertEmpty(\libxml_get_errors());
        $this->assertContains('/packages?search=', $client->getResponse()->getContent());
    }
}
