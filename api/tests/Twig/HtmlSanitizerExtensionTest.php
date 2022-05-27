<?php

namespace App\Tests\Twig;

use App\Twig\HtmlSanitizerExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

class HtmlSanitizerExtensionTest extends TestCase
{
    public function testGetFilters(): void
    {
        /** @var HtmlSanitizerInterface|MockObject $purifier */
        $purifier = $this->createMock(HtmlSanitizerInterface::class);
        $purifier
            ->expects($this->once())
            ->method('sanitize')
            ->with('foo')
            ->willReturn('bar');

        $purifierExtension = new HtmlSanitizerExtension($purifier);

        $this->assertEquals('sanitize', $purifierExtension->getFilters()[0]->getName());
        $this->assertIsCallable($purifierExtension->getFilters()[0]->getCallable());
        $this->assertEquals('bar', $purifierExtension->getFilters()[0]->getCallable()('foo'));
    }
}
