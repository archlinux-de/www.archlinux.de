<?php

namespace App\Tests\Entity\Packages;

use App\Entity\Packages\Files;
use App\Entity\Packages\Package;
use PHPUnit\Framework\TestCase;

class FilesTest extends TestCase
{
    /** @var array */
    private $files = ['usr/bin', 'usr/bin/pacman'];

    public function testGetPackage()
    {
        /** @var Package|\PHPUnit_Framework_MockObject_MockObject $packge */
        $packge = $this->createMock(Package::class);
        $files = $this->createFiles();
        $files->setPackage($packge);
        $this->assertSame($packge, $files->getPackage());
    }

    /**
     * @return Files
     */
    private function createFiles(): Files
    {
        return Files::createFromArray($this->files);
    }

    public function testGetIterator()
    {
        $this->assertEquals($this->files, iterator_to_array($this->createFiles()->getIterator()));
    }

    public function testJsonSerialize()
    {
        $files = $this->createFiles();

        $json = json_encode($files);
        $this->assertJson($json);
        $jsonArray = json_decode($json, true);
        $this->assertEquals($this->files, $jsonArray);
    }
}
