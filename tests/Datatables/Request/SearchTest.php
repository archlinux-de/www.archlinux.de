<?php

namespace App\Tests\Datatables\Request;

use App\Datatables\Request\Search;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Datatables\Request\Search
 */
class SearchTest extends TestCase
{
    /**
     * @param string $value
     * @param bool $isValid
     * @dataProvider provideValidValues
     */
    public function testIsValid(string $value, bool $isValid): void
    {
        $search = new Search($value, false);

        $this->assertEquals($isValid, $search->isValid());
        $this->assertEquals($value, $search->getValue());
    }

    /**
     * @return array<array>
     */
    public function provideValidValues(): array
    {
        return [
            ['', false],
            ['foo', true]
        ];
    }

    /**
     * @param bool $isRegex
     * @dataProvider provideIsRegex
     */
    public function testIsRegex(bool $isRegex): void
    {
        $search = new Search('foo', $isRegex);

        $this->assertEquals($isRegex, $search->isRegex());
        $this->assertEquals('foo', $search->getValue());
    }

    /**
     * @return array<array>
     */
    public function provideIsRegex(): array
    {
        return [
            [true],
            [false]
        ];
    }
}
