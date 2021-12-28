<?php

namespace App\Tests\Twig;

use App\Twig\PurifierExtension;
use HTMLPurifier;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PurifierExtensionTest extends TestCase
{
    public function testGetFilters(): void
    {
        /** @var HTMLPurifier|MockObject $purifier */
        $purifier = $this->createMock(HTMLPurifier::class);
        $purifier
            ->expects($this->once())
            ->method('purify')
            ->with('foo')
            ->willReturn('bar');

        $purifierExtension = new PurifierExtension($purifier);

        $this->assertEquals('purify', $purifierExtension->getFilters()[0]->getName());
        $this->assertIsCallable($purifierExtension->getFilters()[0]->getCallable());
        $this->assertEquals('bar', $purifierExtension->getFilters()[0]->getCallable()('foo'));
    }
}
