<?php

namespace App\Tests\Entity\Packages\Relations;

use App\Entity\Packages\Relations\Dependency;
use PHPUnit\Framework\TestCase;

class AbstractRelationTest extends TestCase
{
    public function testEntity(): void
    {
        $dependency = new Dependency('php', '>=7.2');
        $this->assertEquals('php', $dependency->getTargetName());
        $this->assertEquals('>=7.2', $dependency->getTargetVersion());
    }
}
