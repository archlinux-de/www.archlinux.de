<?php

namespace App\Tests\ArchLinux;

use App\ArchLinux\Package;
use App\ArchLinux\PackageDatabase;
use App\ArchLinux\PackageDatabaseReader;
use PHPUnit\Framework\TestCase;

class PackageDatabaseTest extends TestCase
{
    public function testIteratorCreatesPackage()
    {
        /** @var PackageDatabaseReader|\PHPUnit_Framework_MockObject_MockObject $reader */
        $reader = $this->createMock(PackageDatabaseReader::class);
        $reader->method('extract')->willReturn(
            $this->createFilesystemIteratorMock([
                $this->createMock(\SplFileInfo::class)
            ])
        );

        $database = new PackageDatabase($reader);

        $packageArray = iterator_to_array($database);
        $this->assertCount(1, $packageArray);
        $this->assertInstanceOf(Package::class, array_pop($packageArray));
    }

    /**
     * @param array $fileNames
     * @return \PHPUnit_Framework_MockObject_MockObject|\FilesystemIterator
     */
    private function createFilesystemIteratorMock(array $fileNames = []): \FilesystemIterator
    {
        $filesystemIterator = $this->createMock(\FilesystemIterator::class);
        $iterator = new \ArrayIterator($fileNames);

        $filesystemIterator
            ->expects($this->any())
            ->method('rewind')
            ->willReturnCallback(function () use ($iterator) {
                $iterator->rewind();
            });

        $filesystemIterator
            ->expects($this->any())
            ->method('current')
            ->willReturnCallback(function () use ($iterator) {
                return $iterator->current();
            });

        $filesystemIterator
            ->expects($this->any())
            ->method('key')
            ->willReturnCallback(function () use ($iterator) {
                return $iterator->key();
            });

        $filesystemIterator
            ->expects($this->any())
            ->method('next')
            ->willReturnCallback(function () use ($iterator) {
                $iterator->next();
            });

        $filesystemIterator
            ->expects($this->any())
            ->method('valid')
            ->willReturnCallback(function () use ($iterator) {
                return $iterator->valid();
            });

        return $filesystemIterator;
    }

    public function testReaderIsOnlyCalledOnce()
    {
        /** @var PackageDatabaseReader|\PHPUnit_Framework_MockObject_MockObject $reader */
        $reader = $this->createMock(PackageDatabaseReader::class);
        $reader->expects($this->once())->method('extract')->willReturn(
            $this->createFilesystemIteratorMock([
                $this->createMock(\SplFileInfo::class),
                $this->createMock(\SplFileInfo::class)
            ])
        );

        $database = new PackageDatabase($reader);

        $this->assertCount(2, iterator_to_array($database));
    }
}
