<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable
 */
class Torrent
{

    /**
     * @var string
     *
     * @ORM\Column(nullable=true)
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $comment;

    /**
     * @var string
     *
     * @ORM\Column(nullable=true)
     */
    private $infoHash;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $pieceLength;

    /**
     * @var string
     *
     * @ORM\Column(nullable=true)
     */
    private $fileName;

    /**
     * @var string
     *
     * @ORM\Column(nullable=true)
     */
    private $announce;

    /**
     * @var integer
     *
     * @ORM\Column(type="bigint", nullable=true)
     */
    private $fileLength;

    /**
     * @var integer
     *
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $pieceCount;

    /**
     * @var string
     *
     * @ORM\Column(nullable=true)
     */
    private $createdBy;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $creationDate;

    /**
     * @var string
     *
     * @ORM\Column(nullable=true)
     */
    private $magnetUri;

    /**
     * @return string
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @param string $url
     * @return Torrent
     */
    public function setUrl(?string $url): Torrent
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @return string
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     * @return Torrent
     */
    public function setComment(?string $comment): Torrent
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     * @return string
     */
    public function getInfoHash(): ?string
    {
        return $this->infoHash;
    }

    /**
     * @param string $infoHash
     * @return Torrent
     */
    public function setInfoHash(?string $infoHash): Torrent
    {
        $this->infoHash = $infoHash;
        return $this;
    }

    /**
     * @return int
     */
    public function getPieceLength(): ?int
    {
        return $this->pieceLength;
    }

    /**
     * @param int $pieceLength
     * @return Torrent
     */
    public function setPieceLength(?int $pieceLength): Torrent
    {
        $this->pieceLength = $pieceLength;
        return $this;
    }

    /**
     * @return string
     */
    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    /**
     * @param string $fileName
     * @return Torrent
     */
    public function setFileName(?string $fileName): Torrent
    {
        $this->fileName = $fileName;
        return $this;
    }

    /**
     * @return string
     */
    public function getAnnounce(): ?string
    {
        return $this->announce;
    }

    /**
     * @param string $announce
     * @return Torrent
     */
    public function setAnnounce(?string $announce): Torrent
    {
        $this->announce = $announce;
        return $this;
    }

    /**
     * @return int
     */
    public function getFileLength(): ?int
    {
        return $this->fileLength;
    }

    /**
     * @param int $fileLength
     * @return Torrent
     */
    public function setFileLength(?int $fileLength): Torrent
    {
        $this->fileLength = $fileLength;
        return $this;
    }

    /**
     * @return int
     */
    public function getPieceCount(): ?int
    {
        return $this->pieceCount;
    }

    /**
     * @param int $pieceCount
     * @return Torrent
     */
    public function setPieceCount(?int $pieceCount): Torrent
    {
        $this->pieceCount = $pieceCount;
        return $this;
    }

    /**
     * @return string
     */
    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    /**
     * @param string $createdBy
     * @return Torrent
     */
    public function setCreatedBy(?string $createdBy): Torrent
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreationDate(): ?\DateTime
    {
        return $this->creationDate;
    }

    /**
     * @param \DateTime $creationDate
     * @return Torrent
     */
    public function setCreationDate(?\DateTime $creationDate): Torrent
    {
        $this->creationDate = $creationDate;
        return $this;
    }

    /**
     * @return string
     */
    public function getMagnetUri(): ?string
    {
        return $this->magnetUri;
    }

    /**
     * @param string $magnetUri
     * @return Torrent
     */
    public function setMagnetUri(?string $magnetUri): Torrent
    {
        $this->magnetUri = $magnetUri;
        return $this;
    }
}
