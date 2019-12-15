<?php

namespace App\Service;

class Slugger
{
    /**
     * @param string $input
     * @return string
     */
    public function slugify(string $input): string
    {
        return trim(
            (string)preg_replace(
                ['/[^a-z0-9_\-.]+/', '/-+/'],
                '-',
                $this->translit(
                    mb_strtolower($input)
                )
            ),
            '_-.'
        );
    }

    /**
     * @param string $input
     * @return string
     */
    private function translit(string $input): string
    {
        return str_replace(
            ['ä', 'ö', 'ü', 'ß'],
            ['ae', 'oe', 'ue', 'ss'],
            $input
        );
    }
}
