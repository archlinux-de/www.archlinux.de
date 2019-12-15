<?php

namespace App\Tests\Service;

use App\Service\Slugger;
use PHPUnit\Framework\TestCase;

class SluggerTest extends TestCase
{
    /**
     * @param string $input
     * @param string $expected
     * @dataProvider provideSlugs
     */
    public function testSlugify(string $input, string $expected): void
    {
        $slugger = new Slugger();

        $this->assertEquals($expected, $slugger->slugify($input));
    }

    /**
     * @return array
     */
    public function provideSlugs(): array
    {
        return [
            ['A', 'a'],
            ['--', ''],
            ['az09_-.b', 'az09_-.b'],
            ['ö', 'oe'],
            ['ü', 'ue'],
            ['ä', 'ae'],
            ['ß', 'ss'],
            ['Ö', 'oe'],
            ['Ü', 'ue'],
            ['Ä', 'ae'],
            ['ẞ', 'ss'],
            ['a^!"§$%&/()=?`²³´}{[]@€#+*~:;,<|>°b', 'a-b'],
            ['cat🐱s', 'cat-s'],
            ['--a--', 'a'],
            ['a--b', 'a-b'],
            ['2.3', '2.3']
        ];
    }
}
