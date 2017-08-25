<?php

namespace Tests\AppBundle\Twig;

use AppBundle\Twig\AppExtension;
use PHPUnit\Framework\TestCase;

class AppExtensionTest extends TestCase
{
    /**
     * @param string $input
     * @param string $output
     * @dataProvider provideByteFormats
     */
    public function testFormatBytes(string $input, string $output)
    {
        $appExtension = new AppExtension();
        $this->assertEquals($output, $appExtension->formatBytes($input));
    }

    /**
     * @return array
     */
    public function provideByteFormats(): array
    {
        return [
            ['1', '1,00 Byte'],
            ['1024', '1,00 KByte'],
            ['1048576', '1,00 MByte'],
            ['1073741824', '1,00 GByte'],
            ['-1', '-1,00 Byte']
        ];
    }

    public function testFormatBytesFailsOnIncorrectInput()
    {
        $appExtension = new AppExtension();
        $this->expectException(\TypeError::class);
        $appExtension->formatBytes('foo');
    }

    /**
     * @param string $component
     * @param string $output
     * @dataProvider provideUrlParserOptions
     */
    public function testParseUrl(string $component, string $output)
    {
        $input = 'https://user:pass@www.archlinux.de:443/path/blah?foo#bar';
        $appExtension = new AppExtension();
        $this->assertEquals($output, $appExtension->parseUrl($input, $component));
    }

    /**
     * @return array
     */
    public function provideUrlParserOptions(): array
    {
        return [
            ['scheme', 'https'],
            ['host', 'www.archlinux.de'],
            ['port', '443'],
            ['user', 'user'],
            ['pass', 'pass'],
            ['path', '/path/blah'],
            ['query', 'foo'],
            ['fragment', 'bar']
        ];
    }

    public function testParseUrlFailsOnIncorrectInput()
    {
        $appExtension = new AppExtension();
        $this->expectException(\RuntimeException::class);
        $appExtension->parseUrl('foo', 'bar');
    }
}
