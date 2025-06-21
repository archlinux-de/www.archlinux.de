<?php

namespace App\Tests\Twig;

use App\Twig\SluggerExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\String\UnicodeString;

class SluggerExtensionTest extends TestCase
{
    public function testSlug(): void
    {
        /** @var SluggerInterface&MockObject $slugger */
        $slugger = $this->createMock(SluggerInterface::class);
        $slugger
            ->expects($this->once())
            ->method('slug')
            ->with('foo')
            ->willReturn(new UnicodeString('bar'));

        $sluggerExtension = new SluggerExtension($slugger);

        $this->assertEquals('bar', $sluggerExtension->slug('foo'));
    }
}
