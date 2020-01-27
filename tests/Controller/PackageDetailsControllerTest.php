<?php

namespace App\Tests\Controller;

use App\Entity\Packages\Architecture;
use App\Entity\Packages\Files;
use App\Entity\Packages\Package;
use App\Entity\Packages\Repository;
use SymfonyDatabaseTest\DatabaseTestCase;

/**
 * @covers \App\Controller\PackageDetailsController
 */
class PackageDetailsControllerTest extends DatabaseTestCase
{
    public function testUnknownPackageReturnsCorrectHttpStatus(): void
    {
        $client = $this->getClient();
        $client->request('GET', '/packages/core/x86_64/not-found');

        $this->assertTrue($client->getResponse()->isNotFound());
    }

    public function testPackageDetails(): void
    {
        $entityManager = $this->getEntityManager();

        $coreRepository = new Repository('core', Architecture::X86_64);
        $pacman = new Package(
            $coreRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        );
        $entityManager->persist($coreRepository);
        $entityManager->persist($pacman);
        $entityManager->flush();

        $client = $this->getClient();
        $crawler = $client->request('GET', '/packages/core/x86_64/pacman');

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertEquals('pacman', $crawler->filter('h1')->text());
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $this->assertStringContainsString('5.0.2-2', $content);
    }

    public function testRedirectToPackageFromAnotherRepository(): void
    {
        $entityManager = $this->getEntityManager();

        $coreRepository = new Repository('core', Architecture::X86_64);
        $pacman = new Package(
            $coreRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        );
        $entityManager->persist($coreRepository);
        $entityManager->persist($pacman);
        $entityManager->flush();

        $client = $this->getClient();
        $client->request('GET', '/packages/testing/x86_64/pacman');

        $this->assertTrue($client->getResponse()->isRedirection());
    }

    public function testUnknownPackageReturnsCorrectHttpStatusForFiles(): void
    {
        $client = $this->getClient();
        $client->request('GET', '/packages/core/x86_64/not-found/files');

        $this->assertTrue($client->getResponse()->isNotFound());
    }

    public function testPackageFiles(): void
    {
        $entityManager = $this->getEntityManager();

        $coreRepository = new Repository('core', Architecture::X86_64);
        $pacmanFiles = ['usr/bin', 'usr/bin/pacman'];
        $pacman = new Package(
            $coreRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        );
        $pacman->setFiles(Files::createFromArray($pacmanFiles));
        $entityManager->persist($coreRepository);
        $entityManager->persist($pacman);
        $entityManager->flush();

        $client = $this->getClient();
        $client->request('GET', '/packages/core/x86_64/pacman/files');

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertIsString($client->getResponse()->getContent());
        $this->assertJson($client->getResponse()->getContent());
        $files = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals($pacmanFiles, $files);
    }
}
