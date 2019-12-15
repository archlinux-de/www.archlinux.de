<?php

namespace App\Tests\Entity;

use App\Entity\Release;
use App\Entity\Torrent;
use PHPUnit\Framework\TestCase;

class ReleaseTest extends TestCase
{
    public function testEntity(): void
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

    public function testJsonSerialize(): void
    {
        $releaseDate = new \DateTime('2018-01-01');

        $release = new Release('2018.01.01');
        $release->setReleaseDate($releaseDate);
        $release->setAvailable(true);
        $release->setKernelVersion('3.11');

        $json = (string)json_encode($release);
        $this->assertJson($json);
        $jsonArray = json_decode($json, true);
        $this->assertEquals(
            [
                'version' => '2018.01.01',
                'kernelVersion' => '3.11',
                'releaseDate' => 'Mon, 01 Jan 2018 00:00:00 +0000',
                'available' => true
            ],
            $jsonArray
        );
    }

    public function testUpdate(): void
    {
        $release = (new Release('2019.01.01'))
            ->setTorrent(new Torrent())
            ->setSha1Sum('abc')
            ->setReleaseDate(new \DateTime())
            ->setMd5Sum('cde')
            ->setKernelVersion('2.4.1')
            ->setIsoUrl('foo')
            ->setInfo('bar')
            ->setCreated(new \DateTime())
            ->setAvailable(true);

        $release->update((new Release('2019.01.01'))
            ->setTorrent((new Torrent())->setCreatedBy('me'))
            ->setSha1Sum('1234')
            ->setReleaseDate(new \DateTime('2019-01-01'))
            ->setMd5Sum('5678')
            ->setKernelVersion('1.2')
            ->setIsoUrl('localhost')
            ->setInfo('info')
            ->setCreated(new \DateTime('2018-01-01'))
            ->setAvailable(false));

        $this->assertEquals('me', $release->getTorrent()->getCreatedBy());
        $this->assertEquals('1234', $release->getSha1Sum());
        $this->assertEquals(new \DateTime('2019-01-01'), $release->getReleaseDate());
        $this->assertEquals('5678', $release->getMd5Sum());
        $this->assertEquals('1.2', $release->getKernelVersion());
        $this->assertEquals('localhost', $release->getIsoUrl());
        $this->assertEquals('info', $release->getInfo());
        $this->assertEquals(new \DateTime('2018-01-01'), $release->getCreated());
        $this->assertFalse($release->isAvailable());
    }

    public function testUpdateFailsOnMismatchedVersion(): void
    {
        $release = new Release('foo');
        $this->expectException(\InvalidArgumentException::class);
        $release->update(new Release('bar'));
    }
}
