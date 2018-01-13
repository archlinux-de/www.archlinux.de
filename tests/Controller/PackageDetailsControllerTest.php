<?php

namespace App\Tests\Controller;

use App\Tests\Util\DatabaseTestCase;

/**
 * @covers \App\Controller\PackageDetailsController
 */
class PackageDetailsControllerTest extends DatabaseTestCase
{
    public function testUnknownPackageReturnsCorrectHttpStatus()
    {
        $client = $this->getClient();
        $client->request('GET', '/packages/core/x86_64/not-found');

        $this->assertTrue($client->getResponse()->isNotFound());
    }
}
