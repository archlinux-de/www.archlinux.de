<?php

namespace App\Tests\Serializer;

use App\Entity\Packages\Files;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\Serializer;

class FilesNormalizerTest extends KernelTestCase
{
    private Serializer $serializer;

    public function setUp(): void
    {
        self::bootKernel();
        $this->serializer = self::$container->get('serializer');
    }

    /**
     * @param string[] $filesArray
     * @dataProvider provideFilesArray
     */
    public function testNormalize(array $filesArray): void
    {
        $files = Files::createFromArray($filesArray);

        $json = $this->serializer->serialize($files, 'json');
        $this->assertJson($json);
        $jsonArray = json_decode($json, true);
        $this->assertEquals($filesArray, $jsonArray);
    }

    public function provideFilesArray(): array
    {
        return [
            [[]],
            [['usr/bin', 'usr/bin/pacman']]
        ];
    }
}
