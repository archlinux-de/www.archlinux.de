<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('format_bytes', [$this, 'formatBytes']),
            new TwigFilter('url_path', [$this, 'urlPath']),
            new TwigFilter('url_host', [$this, 'urlHost']),
        ];
    }

    /**
     * @param int $bytes
     *
     * @return string
     */
    public function formatBytes(int $bytes): string
    {
        $kb = 1024;
        $mb = $kb * 1024;
        $gb = $mb * 1024;
        if ($bytes >= $gb) { // GB
            $result = round($bytes / $gb, 2);
            $postfix = 'GByte';
        } elseif ($bytes >= $mb) { // MB
            $result = round($bytes / $mb, 2);
            $postfix = 'MByte';
        } elseif ($bytes >= $kb) { // KB
            $result = round($bytes / $kb, 2);
            $postfix = 'KByte';
        } else {
            //  B
            $result = $bytes;
            $postfix = 'Byte';
        }

        return number_format($result, 2, ',', '.') . ' ' . $postfix;
    }

    /**
     * @param string $url
     * @return string
     */
    public function urlPath(string $url): string
    {
        return (string)parse_url($url, \PHP_URL_PATH);
    }

    /**
     * @param string $url
     * @return string
     */
    public function urlHost(string $url): string
    {
        return (string)parse_url($url, \PHP_URL_HOST);
    }
}
