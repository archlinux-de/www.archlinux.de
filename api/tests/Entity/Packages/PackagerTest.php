<?php

namespace App\Tests\Entity\Packages;

use App\Entity\Packages\Packager;
use PHPUnit\Framework\TestCase;

class PackagerTest extends TestCase
{
    public function testEntity(): void
    {
        $packager = new Packager('foo', 'foo@localhost');
        $this->assertEquals('foo', $packager->getName());
        $this->assertEquals('foo@localhost', $packager->getEmail());
    }
}
