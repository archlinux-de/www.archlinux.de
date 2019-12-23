<?php

namespace App\Tests\Datatables\Request;

use App\Datatables\Request\Column;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Datatables\Request\Column
 */
class ColumnTest extends TestCase
{
    /**
     * @param bool $searchable
     * @param bool $orderable
     * @dataProvider provideColumnFlags
     */
    public function testJsonSerialize(bool $searchable, bool $orderable): void
    {
        $column = new Column(
            0,
            'FooData',
            'FooName',
            $searchable,
            $orderable,
            new \App\Datatables\Request\Search(
                '',
                false
            )
        );

        $json = (string)json_encode($column);
        $this->assertJson($json);
        $jsonArray = json_decode($json, true);
        $this->assertEquals(
            [
                'id' => 0,
                'data' => 'FooData',
                'name' => 'FooName',
                'searchable' => $searchable,
                'orderable' => $orderable,
                'search' => [
                    'value' => '',
                    'regex' => false
                ]
            ],
            $jsonArray
        );
    }

    /**
     * @return array<array>
     */
    public function provideColumnFlags(): array
    {
        $result = [];
        $bools = [true, false];

        foreach ($bools as $searchable) {
            foreach ($bools as $orderable) {
                $result[] = [$searchable, $orderable];
            }
        }

        return $result;
    }
}
