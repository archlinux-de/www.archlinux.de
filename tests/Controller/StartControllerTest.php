<?php

namespace App\Tests\Controller;

use App\Tests\Util\DatabaseTestCase;

/**
 * @covers \App\Controller\StartController
 */
class StartControllerTest extends DatabaseTestCase
{
    public function testIndexAction()
    {
        $client = $this->getClient();

        $crawler = $client->request('GET', '/');

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertContains('Arch Linux', $crawler->filter('h1')->text());
    }
}
