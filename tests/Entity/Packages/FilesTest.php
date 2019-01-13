<?php

namespace App\Tests\Entity\Packages;

use App\Entity\Packages\Files;
use App\Entity\Packages\Package;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FilesTest extends TestCase
{
    /** @var array */
    private $files = ['usr/bin', 'usr/bin/pacman'];

    public function testGetPackage()
    {
        /** @var Package|MockObject $packge */
        $packge = $this->createMock(Package::class);
        $files = Files::createFromArray($this->files);
        $files->setPackage($packge);
        $this->assertSame($packge, $files->getPackage());
    }

    /**
     * @param array $files
     * @dataProvider provideFilesArray
     */
    public function testGetIterator(array $files)
    {
        $this->assertEquals($files, iterator_to_array(Files::createFromArray($files)->getIterator()));
    }

    /**
     * @param array $filesArray
     * @dataProvider provideFilesArray
     */
    public function testJsonSerialize(array $filesArray)
    {
        $files = Files::createFromArray($filesArray);

        $json = (string)json_encode($files);
        $this->assertJson($json);
        $jsonArray = json_decode($json, true);
        $this->assertEquals($filesArray, $jsonArray);
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
