<?php

namespace Tests\App\Twig;

use App\Twig\AppExtension;
use PHPUnit\Framework\TestCase;

class AppExtensionTest extends TestCase
{
    public function testFormatBytesIsCallable()
    {
        $result = call_user_func(
            $this->getFilterCallableFromExtension(new AppExtension(), 'format_bytes'),
            '1'
        );
        $this->assertEquals('1,00 Byte', $result);
    }

    /**
     * @param \Twig_Extension $extension
     * @param string $filterName
     * @return callable
     */
    private function getFilterCallableFromExtension(\Twig_Extension $extension, string $filterName): callable
    {
        /** @var \Twig_Filter $filter */
        foreach ($extension->getFilters() as $filter) {
            if ($filter->getName() == $filterName) {
                return $filter->getCallable();
            }
        }
    }

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

    public function testParseUrlIsCallable()
    {
        $result = call_user_func(
            $this->getFilterCallableFromExtension(new AppExtension(), 'parse_url'),
            'https://www.archlinux.de',
            'host'
        );
        $this->assertEquals('www.archlinux.de', $result);
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
