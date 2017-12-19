<?php

namespace Tests\App\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @coversNothing
 */
class PackageDetailsControllerTest extends WebTestCase
{
    public function testUnknownPackageReturnsCorrectHttpStatus()
    {
        $client = static::createClient();

        $client->request('GET', '/packages/core/x86_64/not-found');

        $this->assertTrue($client->getResponse()->isNotFound());
    }
}
