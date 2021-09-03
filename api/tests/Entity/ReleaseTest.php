<?php

namespace App\Tests\Entity;

use App\Entity\Release;
use PHPUnit\Framework\TestCase;

class ReleaseTest extends TestCase
{
    public function testEntity(): void
    {
        $releaseDate = new \DateTime('2018-01-01');
        $createdDate = new \DateTime('2017-12-31');

        $release = new Release('2018.01.01');
        $this->assertSame($release, $release->setReleaseDate($releaseDate));
        $this->assertSame($release, $release->setCreated($createdDate));
        $this->assertSame($release, $release->setInfo('info'));
        $this->assertSame($release, $release->setAvailable(true));
        $this->assertSame($release, $release->setKernelVersion('3.11'));
        $this->assertSame($release, $release->setSha1Sum('sha1'));
        $this->assertSame($release, $release->setTorrentUrl('/foo.torrent'));

        $this->assertEquals('2018.01.01', $release->getVersion());
        $this->assertSame($releaseDate, $release->getReleaseDate());
        $this->assertSame($createdDate, $release->getCreated());
        $this->assertEquals('info', $release->getInfo());
        $this->assertTrue($release->isAvailable());
        $this->assertEquals('3.11', $release->getKernelVersion());
        $this->assertEquals('sha1', $release->getSha1Sum());
        $this->assertEquals('info', $release->getInfo());

        $this->assertSame('/foo.torrent', $release->getTorrentUrl());
    }

    public function testUpdate(): void
    {
        $release = (new Release('2019.01.01'))
            ->setSha1Sum('abc')
            ->setReleaseDate(new \DateTime('2018-01-01'))
            ->setKernelVersion('2.4.1')
            ->setInfo('bar')
            ->setCreated(new \DateTime('2018-01-01'))
            ->setAvailable(true);

        $release->update((new Release('2019.01.01'))
            ->setSha1Sum('1234')
            ->setReleaseDate(new \DateTime('2019-01-01'))
            ->setKernelVersion('1.2')
            ->setInfo('info')
            ->setCreated(new \DateTime('2018-01-01'))
            ->setAvailable(false));

        $this->assertEquals('1234', $release->getSha1Sum());
        $this->assertEquals(new \DateTime('2019-01-01'), $release->getReleaseDate());
        $this->assertEquals('1.2', $release->getKernelVersion());
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
