<?php

namespace App\Tests\Entity;

use App\Entity\Country;
use PHPUnit\Framework\TestCase;

class CountryTest extends TestCase
{
    public function testJsonSerialize()
    {
        $country = new Country('de');
        $country->setName('Germany');

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
}
