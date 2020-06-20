<?php

namespace App\Service;

class NewsItemIdParser
{
    /**
     * @param string $id
     * @return int
     */
    public function parseId(string $id): int
    {
        if (preg_match('/id=(\d+)/', $id, $matches)) {
            return (int)$matches[1];
        }
        if (preg_match('/^\d+$/', $id)) {
            return (int)$id;
        }
        return (int)hexdec(hash('adler32', $id));
    }
}
