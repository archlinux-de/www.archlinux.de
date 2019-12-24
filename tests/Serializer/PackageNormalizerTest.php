<?php

namespace App\Tests\Serializer;

use App\Entity\Packages\Architecture;
use App\Entity\Packages\Package;
use App\Entity\Packages\Repository;
use App\Serializer\PackageNormalizer;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\Serializer;

class PackageNormalizerTest extends KernelTestCase
{
    /** @var Serializer */
    private $serializer;

    public function setUp(): void
    {
        self::bootKernel();
        $this->serializer = self::$container->get('serializer');
    }

    public function testSupportsNormalization(): void
    {
        $this->assertTrue(
            self::$container->get(PackageNormalizer::class)->supportsNormalization(
                new Package(new Repository('', ''), '', '', '')
            )
        );
    }

    public function testNormalize(): void
    {
        $repository = new Repository('core', Architecture::X86_64);
        $package = new Package($repository, 'pacman', '6.0-1', Architecture::X86_64);
        $package->setBuildDate(new \DateTime('2018-01-30'));
        $package->setDescription('foo bar');
        $package->setGroups(['base']);

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
                    'testing' => false,
                    'url' => 'http://localhost/packages?repository=core&architecture=x86_64'
                ],
                'groups' => [
                    'base'
                ],
                'url' => 'http://localhost/packages/core/x86_64/pacman'
            ],
            $jsonArray
        );
    }
}
