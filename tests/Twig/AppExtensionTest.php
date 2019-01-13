<?php

namespace App\Tests\Twig;

use App\Twig\AppExtension;
use PHPUnit\Framework\TestCase;

class AppExtensionTest extends TestCase
{
    public function testFormatBytesIsCallable()
    {
        $callable = $this->getFilterCallableFromExtension(new AppExtension(), 'format_bytes');
        if (is_callable($callable)) {
            $result = call_user_func(
                $callable,
                '1'
            );
            $this->assertEquals('1,00 Byte', $result);
        } else {
            $this->fail('Filter has no callable');
        }
    }

    /**
     * @param \Twig_Extension $extension
     * @param string $filterName
     * @return callable|null
     */
    private function getFilterCallableFromExtension(\Twig_Extension $extension, string $filterName): ?callable
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
     * @param int $input
     * @param string $output
     * @dataProvider provideByteFormats
     */
    public function testFormatBytes(int $input, string $output)
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
            [1, '1,00 Byte'],
            [1024, '1,00 KByte'],
            [1048576, '1,00 MByte'],
            [1073741824, '1,00 GByte'],
            [-1, '-1,00 Byte']
        ];
    }

    public function testUrlPathIsCallable()
    {
        $callable = $this->getFilterCallableFromExtension(new AppExtension(), 'url_path');
        if (is_callable($callable)) {
            $result = call_user_func(
                $callable,
                'https://www.archlinux.de/packages'
            );
            $this->assertEquals('/packages', $result);
        } else {
            $this->fail('Filter has no callable');
        }
    }

    public function testUrlPath()
    {
        $input = 'https://user:pass@www.archlinux.de:443/path/blah?foo#bar';
        $appExtension = new AppExtension();
        $this->assertEquals('/path/blah', $appExtension->urlPath($input));
    }

    public function testUrlHostIsCallable()
    {
        $callable = $this->getFilterCallableFromExtension(new AppExtension(), 'url_host');
        if (is_callable($callable)) {
            $result = call_user_func(
                $callable,
                'https://www.archlinux.de/packages'
            );
            $this->assertEquals('www.archlinux.de', $result);
        } else {
            $this->fail('Filter has no callable');
        }
    }

    public function testUrlHost()
    {
        $input = 'https://user:pass@www.archlinux.de:443/path/blah?foo#bar';
        $appExtension = new AppExtension();
        $this->assertEquals('www.archlinux.de', $appExtension->urlHost($input));
    }
}
