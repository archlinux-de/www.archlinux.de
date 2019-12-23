<?php

namespace App\Tests\Datatables;

use App\Datatables\DatatablesRequest;
use App\Datatables\Request\Search;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Datatables\DatatablesRequest
 */
class DatatablesRequestTest extends TestCase
{
    /**
     * @param Search|null $search
     * @param bool $hasSearch
     * @dataProvider provideSearches
     */
    public function testHasSearch(?Search $search, bool $hasSearch): void
    {
        $request = new DatatablesRequest(1, 2, 3);
        if ($search !== null) {
            $request->setSearch($search);
        }
        $this->assertEquals($hasSearch, $request->hasSearch());
    }

    /**
     * @return array<array>
     */
    public function provideSearches(): array
    {
        $validSearch = new Search('abc', false);
        $this->assertTrue($validSearch->isValid());

        $invalidSearch = new Search('', false);
        $this->assertFalse($invalidSearch->isValid());

        return [
            [null, false],
            [$validSearch, true],
            [$invalidSearch, false]
        ];
    }

    public function testJsonSerialize(): void
    {
        $request = new DatatablesRequest(1, 2, 3);

        $json = (string)json_encode($request);
        $this->assertJson($json);
        $jsonArray = json_decode($json, true);
        $this->assertEquals(
            [
                'start' => 2,
                'length' => 3,
                'search' => null,
                'order' => [],
                'columns' => []
            ],
            $jsonArray
        );
    }
}
