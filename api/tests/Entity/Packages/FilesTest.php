<?php

namespace App\Tests\Entity\Packages;

use App\Entity\Packages\Files;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FilesTest extends TestCase
{
    /** @var string[] */
    private static array $files = ['usr/bin', 'usr/bin/pacman'];

    public function testGetPackage(): void
    {
        $files = Files::createFromArray(self::$files);
        $this->assertNull($files->getId());
    }

    /**
     * @param string[] $files
     */
    #[DataProvider('provideFilesArray')]
    public function testGetIterator(array $files): void
    {
        $this->assertEquals($files, [...Files::createFromArray($files)->getIterator()]);
    }

    /**
     * @return list<list<string[]>>
     */
    public static function provideFilesArray(): array
    {
        return [
            [[]],
            [self::$files]
        ];
    }
}
