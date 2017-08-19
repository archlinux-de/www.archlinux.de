<?php

namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PostPackageListControllerTest extends WebTestCase
{
    public function testPostPackageListIsSuccessful()
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/statistics',
            ['pkgstatsver' => '2.3', 'arch' => 'x86_64', 'packages' => 'pkgstats', 'modules' => 'snd']
        );

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertContains('Thanks for your submission. :-)', $client->getResponse()->getContent());
    }
}
