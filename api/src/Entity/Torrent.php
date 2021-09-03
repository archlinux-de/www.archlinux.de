<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Embeddable
 */
class Torrent
{
    /**
     * @var string|null
     * @Assert\Length(max="255")
     *
     * @ORM\Column(nullable=true, length=191)
     */
    private $url;

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
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @param string|null $url
     * @return Torrent
     */
    public function setUrl(?string $url): Torrent
    {
        $this->url = $url;
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
     * @return Torrent
     */
    public function setFileName(?string $fileName): Torrent
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
     * @return Torrent
     */
    public function setFileLength(?int $fileLength): Torrent
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
     * @return Torrent
     */
    public function setMagnetUri(?string $magnetUri): Torrent
    {
        $this->magnetUri = $magnetUri;
        return $this;
    }
}
