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
                'name' => 'pacman',
                'repository' => [
                    'name' => 'core',
                    'architecture' => 'x86_64',
                    'testing' => false
                ],
                'packageUrl' => 'http://localhost/download/core/os/x86_64/pacman-5.0.2-2-x86_64.pkg.tar.xz',
                'sourceUrl' => 'https://gitlab.archlinux.org/archlinux/packaging/packages/pacman/-/tree/5.0.2-2',
                'sourceChangelogUrl' =>
                    'https://gitlab.archlinux.org/archlinux/packaging/packages/pacman/-/commits/5.0.2-2',
                'popularity' => 0
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
        $client->request('GET', '/api/packages/core/x86_64/pacman/inverse-dependencies/dependency');

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertIsString($client->getResponse()->getContent());
        $this->assertJson($client->getResponse()->getContent());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertCount(1, $responseData);
        $this->assertEquals('pacman-gui', $responseData[0]['name']);
    }

    public function testPackageInverseDependencyActionFailsWithInvalidType(): void
    {
        $client = $this->getClient();
        $client->request('GET', '/api/packages/core/x86_64/pacman/inverse-dependencies/foo');

        $this->assertTrue($client->getResponse()->isClientError());
    }

    public function testUnknownPackageReturnsCorrectHttpStatus(): void
    {
        $client = $this->getClient();
        $client->request('GET', '/api/packages/core/x86_64/not-found');

        $this->assertTrue($client->getResponse()->isNotFound());
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
        $client->request('GET', '/api/packages/core-testing/x86_64/pacman');

        $this->assertTrue($client->getResponse()->isRedirection());
    }

    public function testUnknownPackageReturnsCorrectHttpStatusForFiles(): void
    {
        $client = $this->getClient();
        $client->request('GET', '/api/packages/core/x86_64/not-found/files');

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
        $client->request('GET', '/api/packages/core/x86_64/pacman/files');

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertIsString($client->getResponse()->getContent());
        $this->assertJson($client->getResponse()->getContent());
        $files = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals($pacmanFiles, $files);
    }
}
