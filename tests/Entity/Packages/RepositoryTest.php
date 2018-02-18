<?php

namespace App\Tests\Entity\Packages;

use App\Entity\Packages\Architecture;
use App\Entity\Packages\Repository;
use PHPUnit\Framework\TestCase;

class RepositoryTest extends TestCase
{
    /**
     * @param bool $testing
     * @dataProvider provideTesting
     */
    public function testJsonSerialize(bool $testing)
    {
        $repository = new Repository('core', Architecture::X86_64);
        $repository->setTesting($testing);

        $json = json_encode($repository);
        $this->assertJson($json);
        $jsonArray = json_decode($json, true);
        $this->assertEquals(
            [
                'name' => 'core',
                'architecture' => Architecture::X86_64,
                'testing' => $testing
            ],
            $jsonArray
        );
    }

    /**
     * @return array
     */
    public function provideTesting(): array
    {
        return [
            [true],
            [false]
        ];
    }
}
