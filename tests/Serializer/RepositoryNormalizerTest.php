<?php

namespace App\Tests\Serializer;

use App\Entity\Packages\Architecture;
use App\Entity\Packages\Repository;
use App\Serializer\RepositoryNormalizer;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\Serializer;

class RepositoryNormalizerTest extends KernelTestCase
{
    /** @var Serializer */
    private $serializer;

    public function setUp(): void
    {
        self::bootKernel();
        $this->serializer = self::$container->get('serializer');
    }

    public function testNormalize(): void
    {
        $this->assertTrue(
            self::$container->get(RepositoryNormalizer::class)->supportsNormalization(
                new Repository('', '')
            )
        );
    }

    /**
     * @param bool $testing
     * @dataProvider provideTesting
     */
    public function testSupportsNormalization(bool $testing): void
    {
        $repository = new Repository('core', Architecture::X86_64);
        $repository->setTesting($testing);

        $json = $this->serializer->serialize($repository, 'json');
        $this->assertJson($json);
        $jsonArray = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals(
            [
                'name' => 'core',
                'architecture' => Architecture::X86_64,
                'testing' => $testing,
                'url' => 'http://localhost/packages?repository=core&architecture=x86_64'
            ],
            $jsonArray
        );
    }

    /**
     * @return array<array<bool>>
     */
    public function provideTesting(): array
    {
        return [
            [true],
            [false]
        ];
    }
}
