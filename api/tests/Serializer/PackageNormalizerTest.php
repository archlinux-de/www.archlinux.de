<?php

namespace App\Tests\Serializer;

use App\Entity\Packages\Architecture;
use App\Entity\Packages\Package;
use App\Entity\Packages\Packager;
use App\Entity\Packages\Repository;
use App\Serializer\PackageNormalizer;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\Serializer;

class PackageNormalizerTest extends KernelTestCase
{
    private Serializer $serializer;

    public function setUp(): void
    {
        self::bootKernel();
        $this->serializer = static::getContainer()->get('serializer');
    }

    public function testSupportsNormalization(): void
    {
        $this->assertTrue(
            static::getContainer()->get(PackageNormalizer::class)->supportsNormalization(
                new Package(new Repository('', ''), '', '', ''),
                'json'
            )
        );
    }

    public function testNormalize(): void
    {
        $repository = new Repository('core', Architecture::X86_64);
        $package = (new Package($repository, 'pacman', '6.0-1', Architecture::X86_64))
            ->setBuildDate(new \DateTime('2018-01-30'))
            ->setDescription('foo bar')
            ->setGroups(['base'])
            ->setUrl('http://www.archlinux.org')
            ->setCompressedSize(123)
            ->setInstalledSize(456)
            ->setPackager(new Packager('Bob', 'bob@localhost'))
            ->setSha256sum('abcdef')
            ->setLicenses(['GPL']);

        $json = $this->serializer->serialize($package, 'json');
        $this->assertJson($json);
        $jsonArray = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals(
            [
                'name' => 'pacman',
                'version' => '6.0-1',
                'architecture' => 'x86_64',
                'description' => 'foo bar',
                'buildDate' => '2018-01-30T00:00:00+00:00',
                'repository' => [
                    'name' => 'core',
                    'architecture' => 'x86_64',
                    'testing' => false
                ],
                'groups' => [
                    'base'
                ],
                'fileName' => 'pacman-6.0-1-x86_64.pkg.tar.xz',
                'url' => 'http://www.archlinux.org',
                'base' => 'pacman',
                'compressedSize' => 123,
                'installedSize' => 456,
                'packager' => [
                    'name' => 'Bob',
                    'email' => 'bob@localhost'
                ],
                'sha256sum' => 'abcdef',
                'licenses' => ['GPL'],
                'packageUrl' => 'http://localhost/download/core/os/x86_64/pacman-6.0-1-x86_64.pkg.tar.xz',
                'sourceUrl' => 'https://projects.archlinux.de/svntogit/packages.git/tree/trunk?h=packages/pacman',
                'sourceChangelogUrl' =>
                    'https://projects.archlinux.de/svntogit/packages.git/log/trunk?h=packages/pacman',
                'popularity' => 0
            ],
            $jsonArray
        );
    }
}
