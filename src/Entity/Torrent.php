<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable
 */
class Torrent
{

    /**
     * @var string|null
     *
     * @ORM\Column(nullable=true, length=191)
     */
    private $url;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $comment;

    /**
     * @var string|null
     *
     * @ORM\Column(nullable=true)
     */
    private $infoHash;

    /**
     * @var integer|null
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $pieceLength;

    /**
     * @var string|null
     *
     * @ORM\Column(nullable=true)
     */
    private $fileName;

    /**
     * @var string|null
     *
     * @ORM\Column(nullable=true)
     */
    private $announce;

    /**
     * @var integer|null
     *
     * @ORM\Column(type="bigint", nullable=true)
     */
    private $fileLength;

    /**
     * @var integer|null
     *
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $pieceCount;

    /**
     * @var string|null
     *
     * @ORM\Column(nullable=true)
     */
    private $createdBy;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $creationDate;

    /**
     * @var string|null
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
    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * @param string|null $comment
     * @return Torrent
     */
    public function setComment(?string $comment): Torrent
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getInfoHash(): ?string
    {
        return $this->infoHash;
    }

    /**
     * @param string|null $infoHash
     * @return Torrent
     */
    public function setInfoHash(?string $infoHash): Torrent
    {
        $this->infoHash = $infoHash;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getPieceLength(): ?int
    {
        return $this->pieceLength;
    }

    /**
     * @param int|null $pieceLength
     * @return Torrent
     */
    public function setPieceLength(?int $pieceLength): Torrent
    {
        $this->pieceLength = $pieceLength;
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
     * @return string|null
     */
    public function getAnnounce(): ?string
    {
        return $this->announce;
    }

    /**
     * @param string|null $announce
     * @return Torrent
     */
    public function setAnnounce(?string $announce): Torrent
    {
        $this->announce = $announce;
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
     * @return int|null
     */
    public function getPieceCount(): ?int
    {
        return $this->pieceCount;
    }

    /**
     * @param int|null $pieceCount
     * @return Torrent
     */
    public function setPieceCount(?int $pieceCount): Torrent
    {
        $this->pieceCount = $pieceCount;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    /**
     * @param string|null $createdBy
     * @return Torrent
     */
    public function setCreatedBy(?string $createdBy): Torrent
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getCreationDate(): ?\DateTime
    {
        return $this->creationDate;
    }

    /**
     * @param \DateTime|null $creationDate
     * @return Torrent
     */
    public function setCreationDate(?\DateTime $creationDate): Torrent
    {
        $this->creationDate = $creationDate;
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
