<?php

namespace App\Tests\Serializer;

use App\Entity\Packages\Architecture;
use App\Entity\Packages\Package;
use App\Entity\Packages\Packager;
use App\Entity\Packages\Repository;
use App\Serializer\PackageNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
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
        $package = new Package($repository, 'pacman', '6.0-1', Architecture::X86_64)
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
                'sourceUrl' => 'https://gitlab.archlinux.org/archlinux/packaging/packages/pacman/-/tree/6.0-1',
                'sourceChangelogUrl' =>
                    'https://gitlab.archlinux.org/archlinux/packaging/packages/pacman/-/commits/6.0-1',
                'popularity' => 0,
                'issueUrl' => 'https://gitlab.archlinux.org/archlinux/packaging/packages/pacman/-/issues'
            ],
            $jsonArray
        );
    }

    #[DataProvider('providePackageNames')]
    public function testGitlabPathConversion(string $packageName, string $expectedPath): void
    {
        $repository = new Repository('core', Architecture::X86_64);
        $package = (new Package($repository, $packageName, '6.0-1', Architecture::X86_64));

        $json = $this->serializer->serialize($package, 'json');
        $this->assertJson($json);
        $jsonArray = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($jsonArray);

        $this->assertStringContainsString($expectedPath, $jsonArray['sourceUrl']);
        $this->assertStringContainsString($expectedPath, $jsonArray['sourceChangelogUrl']);
    }

    /**
     * @return iterable<string[]>
     */
    public static function providePackageNames(): iterable
    {
        return [
            ['mysql++', 'mysqlplusplus'],
            ['foo+bar', 'foo-bar'],
            ['foo+', 'fooplus'],
            ['foo%bar', 'foo-bar'],
            ['foo_bar', 'foo_bar'],
            ['percona-server', 'percona-server'],
            ['lua-std-_debug', 'lua-std-debug'],
            ['tree', 'unix-tree']
        ];
    }

    #[DataProvider('providePackageVersions')]
    public function testGitlabTagConversion(string $packageVersion, string $expectedTag): void
    {
        $repository = new Repository('core', Architecture::X86_64);
        $package = (new Package($repository, 'foo', $packageVersion, Architecture::X86_64));

        $json = $this->serializer->serialize($package, 'json');
        $this->assertJson($json);
        $jsonArray = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($jsonArray);

        $this->assertStringContainsString($expectedTag, $jsonArray['sourceUrl']);
        $this->assertStringContainsString($expectedTag, $jsonArray['sourceChangelogUrl']);
    }

    /**
     * @return iterable<string[]>
     */
    public static function providePackageVersions(): iterable
    {
        return [
            ['1:1.2.3-4', '1-1.2.3-4'],
            ['1.2.3beta-6-4', '1.2.3beta-6-4'],
            ['1.2.3beta-6-4', '1.2.3beta-6-4'],
            ['0.33+0.3.2-4', '0.33+0.3.2-4']
        ];
    }
}
