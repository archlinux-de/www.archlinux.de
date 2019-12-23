<?php

namespace App\Tests\Datatables\Request;

use App\Datatables\Request\Column;
use App\Datatables\Request\Search;
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
    public function testColumn(bool $searchable, bool $orderable): void
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

        $this->assertEquals(0, $column->getId());
        $this->assertEquals('FooData', $column->getData());
        $this->assertEquals('FooName', $column->getName());
        $this->assertEquals($searchable, $column->isSearchable());
        $this->assertEquals($orderable, $column->isOrderable());
        $this->assertInstanceOf(Search::class, $column->getSearch());
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
