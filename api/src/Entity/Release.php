<?php

namespace App\Entity;

use App\Repository\ReleaseRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'releng_release')]
#[ORM\Entity(repositoryClass: ReleaseRepository::class)]
#[ORM\Index(columns: ['available', 'release_date'])]
class Release
{
    #[ORM\Column(length: 191)]
    #[ORM\Id]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Assert\Regex('/^[0-9]+[\.\-\w]+$/')]
    private string $version;

    #[ORM\Column(type: 'boolean')]
    private bool $available;

    #[ORM\Column(type: 'text')]
    #[Assert\Length(max: 16384)]
    private string $info;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $created;

    #[ORM\Column(nullable: true)]
    #[Assert\Regex('/^[\d\.]{5,10}$/')]
    private ?string $kernelVersion = null;

    #[ORM\Column(type: 'date')]
    private \DateTime $releaseDate;

    #[ORM\Column(length: 40, nullable: true)]
    #[Assert\Regex('/^[0-9a-f]{40}$/')]
    private ?string $sha1Sum = null;

    #[ORM\Column(length: 191, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $torrentUrl = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $fileName = null;

    #[ORM\Column(type: 'bigint', nullable: true)]
    #[Assert\Range(min: 1, max: 4294967296)]
    private ?int $fileLength = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $magnetUri = null;

    public function __construct(string $version)
    {
        $this->version = $version;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getKernelVersion(): ?string
    {
        return $this->kernelVersion;
    }

    public function setKernelVersion(?string $kernelVersion): Release
    {
        $this->kernelVersion = $kernelVersion;
        return $this;
    }

    public function getReleaseDate(): \DateTime
    {
        return $this->releaseDate;
    }

    public function setReleaseDate(\DateTime $releaseDate): Release
    {
        $this->releaseDate = $releaseDate;
        return $this;
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function setAvailable(bool $available): Release
    {
        $this->available = $available;
        return $this;
    }

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

    public function getCreated(): \DateTime
    {
        return $this->created;
    }

    public function setCreated(\DateTime $created): Release
    {
        $this->created = $created;
        return $this;
    }

    public function getInfo(): string
    {
        return $this->info;
    }

    public function setInfo(string $info): Release
    {
        $this->info = $info;
        return $this;
    }

    public function getSha1Sum(): ?string
    {
        return $this->sha1Sum;
    }

    public function setSha1Sum(?string $sha1Sum): Release
    {
        $this->sha1Sum = $sha1Sum;
        return $this;
    }

    public function getTorrentUrl(): ?string
    {
        return $this->torrentUrl;
    }

    public function setTorrentUrl(?string $torrentUrl): Release
    {
        $this->torrentUrl = $torrentUrl;
        return $this;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(?string $fileName): Release
    {
        $this->fileName = $fileName;
        return $this;
    }

    public function getFileLength(): ?int
    {
        return $this->fileLength;
    }

    public function setFileLength(?int $fileLength): Release
    {
        $this->fileLength = $fileLength;
        return $this;
    }

    public function getMagnetUri(): ?string
    {
        return $this->magnetUri;
    }

    public function setMagnetUri(?string $magnetUri): Release
    {
        $this->magnetUri = $magnetUri;
        return $this;
    }
}
