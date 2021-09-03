<?php

namespace App\Tests\Entity;

use App\Entity\Torrent;
use PHPUnit\Framework\TestCase;

class TorrentTest extends TestCase
{
    public function testEntity(): void
    {
        $creationDate = new \DateTime('2018-01-01');

        $torrent = new Torrent();
        $this->assertSame($torrent, $torrent->setFileName('file'));
        $this->assertSame($torrent, $torrent->setFileLength(32));
        $this->assertSame($torrent, $torrent->setMagnetUri('magnet'));
        $this->assertSame($torrent, $torrent->setUrl('http://localhost'));

        $this->assertEquals('file', $torrent->getFileName());
        $this->assertEquals(32, $torrent->getFileLength());
        $this->assertEquals('magnet', $torrent->getMagnetUri());
        $this->assertEquals('http://localhost', $torrent->getUrl());
    }
}
