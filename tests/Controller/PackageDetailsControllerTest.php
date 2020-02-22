<?php

namespace App\Tests\Controller;

use App\Entity\Packages\Architecture;
use App\Entity\Packages\Files;
use App\Entity\Packages\Package;
use App\Entity\Packages\Relations\Dependency;
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

    public function testPackageAction(): void
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
        $client->request('GET', '/api/packages/core/x86_64/pacman');

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertIsString($client->getResponse()->getContent());
        $this->assertJson($client->getResponse()->getContent());
        $this->assertEquals(
            [
                'version' => '5.0.2-2',
                'architecture' => 'x86_64',
                'fileName' => 'pacman-5.0.2-2-x86_64.pkg.tar.xz',
                'url' => null,
                'description' => '',
                'base' => 'pacman',
                'buildDate' => null,
                'compressedSize' => 0,
                'installedSize' => 0,
                'packager' => null,
                'sha256sum' => null,
                'licenses' => null,
                'groups' => [],
                'dependencies' => [],
                'conflicts' => [],
                'replacements' => [],
                'optionalDependencies' => [],
                'provisions' => [],
                'makeDependencies' => [],
                'checkDependencies' => [],
                'name' => 'pacman',
                'repository' => [
                    'name' => 'core',
                    'architecture' => 'x86_64',
                    'testing' => false,
                    '_url' => 'http://localhost/packages?repository=core&architecture=x86_64'
                ],
                '_url' => 'http://localhost/packages/core/x86_64/pacman'
            ],
            json_decode($client->getResponse()->getContent(), true)
        );
    }

    public function testPackageInverseDependencyAction(): void
    {
        $entityManager = $this->getEntityManager();

        $coreRepository = new Repository('core', Architecture::X86_64);
        $pacman = new Package(
            $coreRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        );
        $pacmanGui = (new Package(
            $coreRepository,
            'pacman-gui',
            '0.0.1-1',
            Architecture::X86_64
        ))
            ->addDependency((new Dependency('pacman'))->setTarget($pacman));
        $entityManager->persist($coreRepository);
        $entityManager->persist($pacman);
        $entityManager->persist($pacmanGui);
        $entityManager->flush();

        $client = $this->getClient();
        $client->request('GET', '/api/packages/core/x86_64/pacman/inverse/dependency');

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertIsString($client->getResponse()->getContent());
        $this->assertJson($client->getResponse()->getContent());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(1, $responseData['count']);
        $this->assertEquals(1, $responseData['total']);
        $this->assertCount(1, $responseData['items']);
        $this->assertEquals('pacman-gui', $responseData['items'][0]['name']);
    }

    public function testPackageInverseDependencyActionFailsWithInvalidType(): void
    {
        $client = $this->getClient();
        $client->request('GET', '/api/packages/core/x86_64/pacman/inverse/foo');

        $this->assertTrue($client->getResponse()->isClientError());
    }
}
