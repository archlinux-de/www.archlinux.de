<?php

namespace App\Tests\Entity\Packages;

use App\Entity\Packages\Files;
use App\Entity\Packages\Package;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FilesTest extends TestCase
{
    /** @var string[] */
    private $files = ['usr/bin', 'usr/bin/pacman'];

    public function testGetPackage(): void
    {
        /** @var Package|MockObject $packge */
        $packge = $this->createMock(Package::class);
        $files = Files::createFromArray($this->files);
        $files->setPackage($packge);
        $this->assertSame($packge, $files->getPackage());
    }

    /**
     * @param string[] $files
     * @dataProvider provideFilesArray
     */
    public function testGetIterator(array $files): void
    {
        $this->assertEquals($files, [...Files::createFromArray($files)->getIterator()]);
    }

    /**
     * @return array
     */
    public function provideFilesArray(): array
    {
        return [
            [[]],
            [$this->files]
        ];
    }
}
