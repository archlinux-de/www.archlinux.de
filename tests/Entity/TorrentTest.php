<?php

namespace App\Tests\Entity;

use App\Entity\Torrent;
use PHPUnit\Framework\TestCase;

class TorrentTest extends TestCase
{
    public function testEntity(): void
    {
        $creationDate = new \DateTime();

        $torrent = new Torrent();
        $this->assertSame($torrent, $torrent->setFileName('file'));
        $this->assertSame($torrent, $torrent->setAnnounce('announce'));
        $this->assertSame($torrent, $torrent->setComment('comment'));
        $this->assertSame($torrent, $torrent->setCreatedBy('creator'));
        $this->assertSame($torrent, $torrent->setCreationDate($creationDate));
        $this->assertSame($torrent, $torrent->setFileLength(32));
        $this->assertSame($torrent, $torrent->setInfoHash('hash'));
        $this->assertSame($torrent, $torrent->setMagnetUri('magnet'));
        $this->assertSame($torrent, $torrent->setPieceCount(2));
        $this->assertSame($torrent, $torrent->setPieceLength(42));
        $this->assertSame($torrent, $torrent->setUrl('http://localhost'));

        $this->assertEquals('file', $torrent->getFileName());
        $this->assertEquals('announce', $torrent->getAnnounce());
        $this->assertEquals('comment', $torrent->getComment());
        $this->assertEquals('creator', $torrent->getCreatedBy());
        $this->assertSame($creationDate, $torrent->getCreationDate());
        $this->assertEquals(32, $torrent->getFileLength());
        $this->assertEquals('hash', $torrent->getInfoHash());
        $this->assertEquals('magnet', $torrent->getMagnetUri());
        $this->assertEquals(2, $torrent->getPieceCount());
        $this->assertEquals(42, $torrent->getPieceLength());
        $this->assertEquals('http://localhost', $torrent->getUrl());
    }
}
