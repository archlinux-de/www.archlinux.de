<?php

namespace AppBundle\Twig;

class AppExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('format_bytes', array($this, 'formatBytes')),
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
}
