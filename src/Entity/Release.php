<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="releng_release", indexes={@ORM\Index(columns={"available", "release_date"})})
 * @ORM\Entity(repositoryClass="App\Repository\ReleaseRepository")
 */
class Release
{
    /**
     * @var string
     *
     * @ORM\Column()
     * @ORM\Id
     */
    private $version;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    private $available;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    private $info;

    /**
     * @var string
     *
     * @ORM\Column()
     */
    private $isoUrl;

    /**
     * @var string
     *
     * @ORM\Column(length=32, nullable=true)
     */
    private $md5Sum;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @var string
     *
     * @ORM\Column(nullable=true)
     */
    private $kernelVersion;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="date")
     */
    private $releaseDate;

    /**
     * @var string
     *
     * @ORM\Column(length=40, nullable=true)
     */
    private $sha1Sum;

    /**
     * @var Torrent
     *
     * @ORM\Embedded(class="Torrent")
     */
    private $torrent;

    /**
     * @param string $version
     */
    public function __construct(string $version)
    {
        $this->version = $version;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->available;
    }

    /**
     * @param bool $available
     * @return Release
     */
    public function setAvailable(bool $available): Release
    {
        $this->available = $available;
        return $this;
    }

    /**
     * @return string
     */
    public function getInfo(): string
    {
        return $this->info;
    }

    /**
     * @param string $info
     * @return Release
     */
    public function setInfo(string $info): Release
    {
        $this->info = $info;
        return $this;
    }

    /**
     * @return string
     */
    public function getIsoUrl(): string
    {
        return $this->isoUrl;
    }

    /**
     * @param string $isoUrl
     * @return Release
     */
    public function setIsoUrl(string $isoUrl): Release
    {
        $this->isoUrl = $isoUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getMd5Sum(): ?string
    {
        return $this->md5Sum;
    }

    /**
     * @param string $md5Sum
     * @return Release
     */
    public function setMd5Sum(?string $md5Sum): Release
    {
        $this->md5Sum = $md5Sum;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreated(): \DateTime
    {
        return $this->created;
    }

    /**
     * @param \DateTime $created
     * @return Release
     */
    public function setCreated(\DateTime $created): Release
    {
        $this->created = $created;
        return $this;
    }

    /**
     * @return string
     */
    public function getKernelVersion(): ?string
    {
        return $this->kernelVersion;
    }

    /**
     * @param string $kernelVersion
     * @return Release
     */
    public function setKernelVersion(?string $kernelVersion): Release
    {
        $this->kernelVersion = $kernelVersion;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getReleaseDate(): \DateTime
    {
        return $this->releaseDate;
    }

    /**
     * @param \DateTime $releaseDate
     * @return Release
     */
    public function setReleaseDate(\DateTime $releaseDate): Release
    {
        $this->releaseDate = $releaseDate;
        return $this;
    }

    /**
     * @return string
     */
    public function getSha1Sum(): ?string
    {
        return $this->sha1Sum;
    }

    /**
     * @param string $sha1Sum
     * @return Release
     */
    public function setSha1Sum(?string $sha1Sum): Release
    {
        $this->sha1Sum = $sha1Sum;
        return $this;
    }

    /**
     * @return Torrent
     */
    public function getTorrent(): Torrent
    {
        return $this->torrent;
    }

    /**
     * @param Torrent $torrent
     * @return Release
     */
    public function setTorrent(Torrent $torrent): Release
    {
        $this->torrent = $torrent;
        return $this;
    }
}
