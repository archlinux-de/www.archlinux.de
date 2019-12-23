<?php

namespace App\Tests\Datatables\Request;

use App\Datatables\Request\Column;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Datatables\Request\Order
 */
class OrderTest extends TestCase
{
    /**
     * @param string $direction
     * @dataProvider provideDirections
     */
    public function testOrder(string $direction): void
    {
        $column = new Column(
            0,
            '',
            '',
            false,
            false,
            new \App\Datatables\Request\Search(
                '',
                false
            )
        );
        $order = new \App\Datatables\Request\Order(
            $column,
            $direction
        );

        $this->assertSame($column, $order->getColumn());
        $this->assertEquals($direction, $order->getDir());
    }

    /**
     * @return array<array>
     */
    public function provideDirections(): array
    {
        return [
            ['asc'],
            ['desc']
        ];
    }
}
