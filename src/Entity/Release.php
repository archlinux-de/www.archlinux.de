<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="releng_release", indexes={@ORM\Index(columns={"available", "release_date"})})
 * @ORM\Entity(repositoryClass="App\Repository\ReleaseRepository")
 */
class Release implements \JsonSerializable
{
    /**
     * @var string
     * @Assert\NotBlank()
     * @Assert\Length(max="255")
     * @Assert\Regex("/^[0-9]+[\.\-\w]+$/")
     *
     * @ORM\Column(length=191)
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
     * @Assert\Length(max="16384")
     *
     * @ORM\Column(type="text")
     */
    private $info;

    /**
     * @var string
     * @Assert\Length(min="10", max="255")
     *
     * @ORM\Column()
     */
    private $isoUrl;

    /**
     * @var string|null
     * @Assert\Regex("/^[0-9a-f]{32}$/")
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
     * @var string|null
     * @Assert\Regex("/^[\d\.]{5,10}$/")
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
     * @var string|null
     * @Assert\Regex("/^[0-9a-f]{40}$/")
     *
     * @ORM\Column(length=40, nullable=true)
     */
    private $sha1Sum;

    /**
     * @var Torrent
     * @Assert\Valid()
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
     * @return string|null
     */
    public function getMd5Sum(): ?string
    {
        return $this->md5Sum;
    }

    /**
     * @param string|null $md5Sum
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
     * @return string|null
     */
    public function getSha1Sum(): ?string
    {
        return $this->sha1Sum;
    }

    /**
     * @param string|null $sha1Sum
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

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'version' => $this->getVersion(),
            'kernelVersion' => $this->getKernelVersion(),
            'releaseDate' => $this->getReleaseDate()->format(\DateTime::RFC2822),
            'available' => $this->isAvailable()
        ];
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @return string|null
     */
    public function getKernelVersion(): ?string
    {
        return $this->kernelVersion;
    }

    /**
     * @param string|null $kernelVersion
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
}
