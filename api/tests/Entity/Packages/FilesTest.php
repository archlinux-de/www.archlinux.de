<?php

namespace App\Tests\Entity\Packages;

use App\Entity\Packages\Files;
use PHPUnit\Framework\TestCase;

class FilesTest extends TestCase
{
    private array $files = ['usr/bin', 'usr/bin/pacman'];

    public function testGetPackage(): void
    {
        $files = Files::createFromArray($this->files);
        $this->assertNull($files->getId());
    }

    /**
     * @param string[] $files
     * @dataProvider provideFilesArray
     */
    public function testGetIterator(array $files): void
    {
        $this->assertEquals($files, [...Files::createFromArray($files)->getIterator()]);
    }

    public function provideFilesArray(): array
    {
        return [
            [[]],
            [$this->files]
        ];
    }
}
