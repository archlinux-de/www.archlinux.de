<?php

namespace App\Twig;

class AppExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_Filter('format_bytes', array($this, 'formatBytes')),
            new \Twig_Filter('parse_url', array($this, 'parseUrl')),
        );
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
     * @param string $component
     * @return string
     */
    public function parseUrl(string $url, string $component): string
    {
        switch ($component) {
            case 'scheme':
                $componentId = \PHP_URL_SCHEME;
                break;
            case 'host':
                $componentId = \PHP_URL_HOST;
                break;
            case 'port':
                $componentId = \PHP_URL_PORT;
                break;
            case 'user':
                $componentId = \PHP_URL_USER;
                break;
            case 'pass':
                $componentId = \PHP_URL_PASS;
                break;
            case 'path':
                $componentId = \PHP_URL_PATH;
                break;
            case 'query':
                $componentId = \PHP_URL_QUERY;
                break;
            case 'fragment':
                $componentId = \PHP_URL_FRAGMENT;
                break;
            default:
                throw new \RuntimeException('Unknown component: ' . $component);
        }
        return (string)parse_url($url, $componentId);
    }
}
