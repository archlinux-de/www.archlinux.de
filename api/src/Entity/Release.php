<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="releng_release", indexes={@ORM\Index(columns={"available", "release_date"})})
 * @ORM\Entity(repositoryClass="App\Repository\ReleaseRepository")
 */
class Release
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
     * @var string|null
     * @Assert\Length(max="255")
     *
     * @ORM\Column(nullable=true, length=191)
     */
    private $torrentUrl;

    /**
     * @var string|null
     * @Assert\Length(max="255")
     *
     * @ORM\Column(nullable=true)
     */
    private $fileName;

    /**
     * @var integer|null
     * @Assert\Range(min="1", max="4294967296")
     *
     * @ORM\Column(type="bigint", nullable=true)
     */
    private $fileLength;

    /**
     * @var string|null
     * @Assert\Length(max="255")
     *
     * @ORM\Column(nullable=true)
     */
    private $magnetUri;

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

    /**
     * @param Release $release
     * @return Release
     */
    public function update(Release $release): Release
    {
        if ($this->getVersion() !== $release->getVersion()) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Version mismatch "%s" instead of "%s"',
                    $release->getVersion(),
                    $this->getVersion()
                )
            );
        }
        return $this
            ->setAvailable($release->isAvailable())
            ->setCreated($release->getCreated())
            ->setInfo($release->getInfo())
            ->setKernelVersion($release->getKernelVersion())
            ->setReleaseDate($release->getReleaseDate())
            ->setSha1Sum($release->getSha1Sum())
            ->setTorrentUrl($release->torrentUrl)
            ->setFileName($release->getFileName())
            ->setFileLength($release->getFileLength())
            ->setMagnetUri($release->getMagnetUri());
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
     * @return string|null
     */
    public function getTorrentUrl(): ?string
    {
        return $this->torrentUrl;
    }

    /**
     * @param string|null $torrentUrl
     * @return Release
     */
    public function setTorrentUrl(?string $torrentUrl): Release
    {
        $this->torrentUrl = $torrentUrl;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    /**
     * @param string|null $fileName
     * @return Release
     */
    public function setFileName(?string $fileName): Release
    {
        $this->fileName = $fileName;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getFileLength(): ?int
    {
        return $this->fileLength;
    }

    /**
     * @param int|null $fileLength
     * @return Release
     */
    public function setFileLength(?int $fileLength): Release
    {
        $this->fileLength = $fileLength;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getMagnetUri(): ?string
    {
        return $this->magnetUri;
    }

    /**
     * @param string|null $magnetUri
     * @return Release
     */
    public function setMagnetUri(?string $magnetUri): Release
    {
        $this->magnetUri = $magnetUri;
        return $this;
    }
}
