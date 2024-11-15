<?php

namespace App\Tests\Serializer;

use App\Serializer\PacmanDatabaseDecoder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PacmanDatabaseDecoderTest extends TestCase
{
    public function testSupportsDecoding(): void
    {
        $pacmanDatabaseDecoder = new PacmanDatabaseDecoder();

        $this->assertTrue($pacmanDatabaseDecoder->supportsDecoding('pacman-database'));
    }

    /**
     * @param mixed[] $expected
     */
    #[DataProvider('provideInput')]
    public function testDecode(string $input, array $expected): void
    {
        $pacmanDatabaseDecoder = new PacmanDatabaseDecoder();

        $this->assertSame($expected, $pacmanDatabaseDecoder->decode($input, 'pacman-database'));
    }

    /**
     * @return list<mixed[]>
     */
    public static function provideInput(): array
    {
        return [
            ['', []],
            ['%FOO%', ['FOO' => null]],
            ["%FOO%\nbar", ['FOO' => 'bar']],
            ["%FOO%\nbar\nbaz", ['FOO' => ['bar', 'baz']]],
            ["%FOO%\nbar\n%FOO2%\nbaz", ['FOO' => 'bar', 'FOO2' => 'baz']],
            ['foo', []],
            ['%%', []],
            ["%FOO%\n ", ['FOO' => null]],
            ["%FOO%\n bar", ['FOO' => 'bar']],
            ["%FOO%\n42", ['FOO' => 42]],
            ["%FOO%\n42a", ['FOO' => '42a']],
        ];
    }
}
