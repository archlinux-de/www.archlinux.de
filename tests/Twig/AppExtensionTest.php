<?php

namespace App\Tests\Twig;

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
        throw new \RuntimeException('Filter "' . $filterName . '" was not found.');
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

    public function testUrlPathIsCallable()
    {
        $result = call_user_func(
            $this->getFilterCallableFromExtension(new AppExtension(), 'url_path'),
            'https://www.archlinux.de/packages'
        );
        $this->assertEquals('/packages', $result);
    }

    public function testUrlPath()
    {
        $input = 'https://user:pass@www.archlinux.de:443/path/blah?foo#bar';
        $appExtension = new AppExtension();
        $this->assertEquals('/path/blah', $appExtension->urlPath($input));
    }

    public function testUrlHostIsCallable()
    {
        $result = call_user_func(
            $this->getFilterCallableFromExtension(new AppExtension(), 'url_host'),
            'https://www.archlinux.de/packages'
        );
        $this->assertEquals('www.archlinux.de', $result);
    }

    public function testUrlHost()
    {
        $input = 'https://user:pass@www.archlinux.de:443/path/blah?foo#bar';
        $appExtension = new AppExtension();
        $this->assertEquals('www.archlinux.de', $appExtension->urlHost($input));
    }
}
