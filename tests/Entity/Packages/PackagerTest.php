<?php

namespace App\Tests\Entity\Packages;

use App\Entity\Packages\Packager;
use PHPUnit\Framework\TestCase;

class PackagerTest extends TestCase
{
    /**
     * @param string $input
     * @param string $name
     * @param string $email
     * @dataProvider providePackagers
     */
    public function testCreateFromString(string $input, string $name, string $email): void
    {
        $packager = Packager::createFromString($input);
        $this->assertEquals($name, $packager->getName());
        $this->assertEquals($email, $packager->getEmail());
    }

    /**
     * @return array
     */
    public function providePackagers(): array
    {
        return [
            ['foo<foo@localhost>', 'foo', 'foo@localhost'],
            ['foo', 'foo', '']
        ];
    }

    public function testJsonSerialize(): void
    {
        $packager = new Packager('foo', 'foo@localhost');

        $json = (string)json_encode($packager);
        $this->assertJson($json);
        $jsonArray = json_decode($json, true);
        $this->assertEquals(
            [
                'name' => 'foo',
                'email' => 'foo@localhost'
            ],
            $jsonArray
        );
    }
}
