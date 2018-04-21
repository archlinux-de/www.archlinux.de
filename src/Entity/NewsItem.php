<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(indexes={@ORM\Index(columns={"last_modified"})})
 * @ORM\Entity(repositoryClass="App\Repository\NewsItemRepository")
 */
class NewsItem
{
    /**
     * @var string
     *
     * @ORM\Column(length=191)
     * @ORM\Id
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column()
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column()
     */
    private $link;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    private $description;

    /**
     * @var NewsAuthor
     *
     * @ORM\Embedded(class="NewsAuthor")
     */
    private $author;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $lastModified;

    /**
     * @param string $id
     */
    public function __construct(string $id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return NewsItem
     */
    public function setTitle(string $title): NewsItem
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getLink(): string
    {
        return $this->link;
    }

    /**
     * @param string $link
     * @return NewsItem
     */
    public function setLink(string $link): NewsItem
    {
        $this->link = $link;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return NewsItem
     */
    public function setDescription(string $description): NewsItem
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return NewsAuthor
     */
    public function getAuthor(): NewsAuthor
    {
        return $this->author;
    }

    /**
     * @param NewsAuthor $author
     * @return NewsItem
     */
    public function setAuthor(NewsAuthor $author): NewsItem
    {
        $this->author = $author;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLastModified(): \DateTime
    {
        return $this->lastModified;
    }

    /**
     * @param \DateTime $lastModified
     * @return NewsItem
     */
    public function setLastModified(\DateTime $lastModified): NewsItem
    {
        $this->lastModified = $lastModified;
        return $this;
    }
}
