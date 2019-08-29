<?php

namespace App\Tests\Controller;

use App\Entity\Packages\Architecture;
use App\Entity\Packages\Package;
use App\Entity\Packages\Repository;
use SymfonyDatabaseTest\DatabaseTestCase;

/**
 * @covers \App\Controller\PackagesSuggestController
 */
class PackagesSuggestControllerTest extends DatabaseTestCase
{
    public function testEmptySuggest()
    {
        $client = $this->getClient();

        $client->request('GET', '/packages/suggest', ['term' => '']);

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertIsString($client->getResponse()->getContent());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(0, $responseData);
    }

    public function testSuggest()
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

        $client->request('GET', '/packages/suggest', ['term' => 'pac']);

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertIsString($client->getResponse()->getContent());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $responseData);
        $this->assertEquals('pacman', $responseData[0]);
    }
}
