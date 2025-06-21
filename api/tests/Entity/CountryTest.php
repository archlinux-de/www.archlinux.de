<?php

namespace App\Tests\Entity;

use App\Entity\Country;
use PHPUnit\Framework\TestCase;

class CountryTest extends TestCase
{
    public function testUpdate(): void
    {
        $country = new Country('de')
            ->setName('Germany');

        $this->assertEquals('Germany', $country->getName());
        $country->update(new Country('de')->setName('Deutschland'));
        $this->assertEquals('Deutschland', $country->getName());
    }

    public function testUpdateFailsOnMismatchedCode(): void
    {
        $country = new Country('de')
            ->setName('Germany');

        $this->assertEquals('Germany', $country->getName());
        $this->expectException(\InvalidArgumentException::class);
        $country->update(new Country('ger')->setName('Deutschland'));
    }
}
