<?php

namespace App\Tests\Controller;

use App\Controller\MirrorController;
use App\Entity\Country;
use App\Entity\Mirror;
use App\Entity\Packages\Architecture;
use App\Entity\Packages\Package;
use App\Entity\Packages\Repository;
use App\Entity\Release;
use App\Tests\DatabaseSearchTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(MirrorController::class)]
class MirrorControllerTest extends DatabaseSearchTestCase
{
    public function testIsoAction(): void
    {
        $entityManager = $this->getEntityManager();
        $country = (new Country('de'))->setName('Germany');

        $mirror = (new Mirror('https://127.0.0.2/'))
            ->setScore(1)
            ->setLastSync(new \DateTime('2020-01-01'))
            ->setCountry($country);

        $release = (new Release('2018.01.01'))
            ->setAvailable(true)
            ->setInfo('')
            ->setCreated(new \DateTime('2018-01-01'))
            ->setReleaseDate(new \DateTime('2018-01-01'))
            ->setFileLength(1);

        $entityManager->persist($country);
        $entityManager->persist($mirror);
        $entityManager->persist($release);
        $entityManager->flush();

        $filePath = 'iso/2018.01.01/archlinux-2018.01.01-x86_64.iso';
        $client = $this->getClient();

        $client->request('GET', '/download/' . $filePath);
        $this->assertTrue($client->getResponse()->isRedirect('https://127.0.0.2/' . $filePath));
    }

    public function testFailIfIsoIsUnkown(): void
    {
        $entityManager = $this->getEntityManager();
        $mirror = (new Mirror('https://127.0.0.2/'))
            ->setScore(1)
            ->setLastSync(new \DateTime('2020-01-01'));
        $entityManager->persist($mirror);
        $entityManager->flush();

        $filePath = 'iso/2018.01.01/archlinux-2018.01.01-x86_64.iso';
        $client = $this->getClient();

        $client->request('GET', '/download/' . $filePath);
        $this->assertTrue($client->getResponse()->isNotFound());
    }

    #[DataProvider('providePackageExtensions')]
    public function testPackageAction(string $packageExtension): void
    {
        $entityManager = $this->getEntityManager();
        $mirror = (new Mirror('https://127.0.0.2/'))
            ->setScore(1)
            ->setLastSync(new \DateTime('2020-01-01'));
        $coreRepository = new Repository('core', Architecture::X86_64);
        $pacman = new Package(
            $coreRepository,
            'linux',
            '3.11-1',
            Architecture::X86_64
        );
        $entityManager->persist($mirror);
        $entityManager->persist($coreRepository);
        $entityManager->persist($pacman);
        $entityManager->flush();

        $filePath = 'core/os/x86_64/linux-3.11-1-x86_64.pkg.tar.' . $packageExtension;
        $client = $this->getClient();

        $client->request('GET', '/download/' . $filePath);
        $this->assertTrue($client->getResponse()->isRedirect('https://127.0.0.2/' . $filePath));
    }

    public function testPackageNotFoundAction(): void
    {
        $entityManager = $this->getEntityManager();
        $mirror = (new Mirror('https://127.0.0.2/'))
            ->setScore(1)
            ->setLastSync(new \DateTime('2020-01-01'));
        $entityManager->persist($mirror);
        $entityManager->flush();

        $filePath = 'core/os/x86_64/linux-3.11-1-x86_64.pkg.tar.xz';
        $client = $this->getClient();

        $client->request('GET', '/download/' . $filePath);
        $this->assertTrue($client->getResponse()->isNotFound());
    }

    public function testInvalidPackageNotFoundAction(): void
    {
        $entityManager = $this->getEntityManager();
        $mirror = (new Mirror('https://127.0.0.2/'))
            ->setScore(1)
            ->setLastSync(new \DateTime('2020-01-01'));
        $entityManager->persist($mirror);
        $entityManager->flush();

        $filePath = 'core/os/x86_64/linux-3.11-1-2-1-2-4-x86_64.pkg.tar.xz';
        $client = $this->getClient();

        $client->request('GET', '/download/' . $filePath);
        $this->assertTrue($client->getResponse()->isNotFound());
    }

    public function testFallbackAction(): void
    {
        $entityManager = $this->getEntityManager();
        $mirror = (new Mirror('https://127.0.0.2/'))
            ->setScore(1)
            ->setLastSync(new \DateTime('2020-01-01'));
        $entityManager->persist($mirror);
        $entityManager->flush();

        $filePath = 'foo.txt';
        $client = $this->getClient();

        $client->request('GET', '/download/' . $filePath);
        $this->assertTrue($client->getResponse()->isRedirect('https://127.0.0.2/' . $filePath));
    }

    public function testMirrorNotFound(): void
    {
        $filePath = 'foo.txt';
        $client = $this->getClient();

        $client->request('GET', '/download/' . $filePath);
        $this->assertTrue($client->getResponse()->isNotFound());
    }

    public static function providePackageExtensions(): array
    {
        return [
            ['gz'],
            ['xz'],
            ['zst'],
        ];
    }
}
