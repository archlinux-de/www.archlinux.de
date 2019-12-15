<?php

namespace App\Tests\Entity;

use App\Entity\Country;
use PHPUnit\Framework\TestCase;

class CountryTest extends TestCase
{
    public function testJsonSerialize(): void
    {
        $country = (new Country('de'))
            ->setName('Germany');

        $json = (string)json_encode($country);
        $this->assertJson($json);
        $jsonArray = json_decode($json, true);
        $this->assertEquals(
            [
                'code' => 'de',
                'name' => 'Germany'
            ],
            $jsonArray
        );
    }

    public function testUpdate(): void
    {
        $country = (new Country('de'))
            ->setName('Germany');

        $this->assertEquals('Germany', $country->getName());
        $country->update((new Country('de'))->setName('Deutschland'));
        $this->assertEquals('Deutschland', $country->getName());
    }

    public function testUpdateFailsOnMismatchedCode(): void
    {
        $country = (new Country('de'))
            ->setName('Germany');

        $this->assertEquals('Germany', $country->getName());
        $this->expectException(\InvalidArgumentException::class);
        $country->update((new Country('ger'))->setName('Deutschland'));
    }
}
