<?php

namespace App\Tests\Entity;

use App\Entity\Release;
use App\Entity\Torrent;
use PHPUnit\Framework\TestCase;

class ReleaseTest extends TestCase
{
    public function testEntity()
    {
        $releaseDate = new \DateTime('2018-01-01');
        $createdDate = new \DateTime('2017-12-31');

        $torrent = new Torrent();

        $release = new Release('2018.01.01');
        $this->assertSame($release, $release->setReleaseDate($releaseDate));
        $this->assertSame($release, $release->setCreated($createdDate));
        $this->assertSame($release, $release->setIsoUrl('http://localhost'));
        $this->assertSame($release, $release->setInfo('info'));
        $this->assertSame($release, $release->setAvailable(true));
        $this->assertSame($release, $release->setKernelVersion('3.11'));
        $this->assertSame($release, $release->setMd5Sum('md5'));
        $this->assertSame($release, $release->setSha1Sum('sha1'));
        $this->assertSame($release, $release->setTorrent($torrent));

        $this->assertEquals('2018.01.01', $release->getVersion());
        $this->assertSame($releaseDate, $release->getReleaseDate());
        $this->assertSame($createdDate, $release->getCreated());
        $this->assertEquals('http://localhost', $release->getIsoUrl());
        $this->assertEquals('info', $release->getInfo());
        $this->assertTrue($release->isAvailable());
        $this->assertEquals('3.11', $release->getKernelVersion());
        $this->assertEquals('md5', $release->getMd5Sum());
        $this->assertEquals('sha1', $release->getSha1Sum());
        $this->assertEquals('info', $release->getInfo());

        $this->assertSame($torrent, $release->getTorrent());
    }
}
