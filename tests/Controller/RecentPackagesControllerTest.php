<?php

namespace App\Tests\Controller;

use App\Entity\Packages\Architecture;
use App\Entity\Packages\Package;
use App\Entity\Packages\Packager;
use App\Entity\Packages\Repository;
use App\Tests\Util\DatabaseTestCase;

/**
 * @covers \App\Controller\RecentPackagesController
 */
class RecentPackagesControllerTest extends DatabaseTestCase
{
    public function testIndexAction()
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
        $response = $client->getResponse()->getContent();
        $xml = \simplexml_load_string($response);
        $this->assertNotFalse($xml);
        $this->assertEmpty(\libxml_get_errors());
        $this->assertEquals($php->getName() . ' ' . $php->getVersion(), $xml->entry->title->__toString());
        $this->assertEquals($php->getDescription(), $xml->entry->content->__toString());
        $this->assertNotNull($xml->entry->link);
        $this->assertNotNull($xml->entry->link->attributes());
        $this->assertEquals(
            'http://localhost/packages/core/x86_64/php',
            $xml->entry->link->attributes()->href->__toString()
        );
    }
}
