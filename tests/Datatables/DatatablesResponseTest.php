<?php

namespace App\Tests\Datatables;

use App\Datatables\DatatablesResponse;
use PHPUnit\Framework\TestCase;

class DatatablesResponseTest extends TestCase
{
    public function testInitialization(): void
    {
        $response = new DatatablesResponse(['foo' => 'bar']);

        $this->assertEquals(['foo' => 'bar'], $response->getData());
    }
}
