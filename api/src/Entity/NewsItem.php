<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(indexes={@ORM\Index(columns={"last_modified"})})
 * @ORM\Entity(repositoryClass="App\Repository\NewsItemRepository")
 */
class NewsItem
{
    /**
     * @var int
     * @Assert\NotBlank()
     * @Assert\Range(min="1", max="2147483648")
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     */
    private $id;

    /**
     * @var string
     * @Assert\NotBlank()
     *
     * @ORM\Column()
     */
    private $title;

    /**
     * @var string
     * @Assert\NotBlank()
     * @Assert\Length(min="10", max="255")
     *
     * @ORM\Column(unique=true)
     */
    private $link;

    /**
     * @var string
     * @Assert\NotBlank()
     * @Assert\Length(max="65535")
     *
     * @ORM\Column(type="text")
     */
    private $description;

    /**
     * @var NewsAuthor
     * @Assert\Valid()
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
     * @param int $id
     */
    public function __construct(int $id)
    {
        $this->id = $id;
    }

    /**
     * @param NewsItem $newsItem
     * @return NewsItem
     */
    public function update(NewsItem $newsItem): NewsItem
    {
        if ($this->getId() !== $newsItem->getId()) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Id mismatch "%d" instead of "%d"',
                    $newsItem->getId(),
                    $this->getId()
                )
            );
        }
        return $this
            ->setAuthor($newsItem->getAuthor())
            ->setDescription($newsItem->getDescription())
            ->setLastModified($newsItem->getLastModified())
            ->setLink($newsItem->getLink())
            ->setTitle($newsItem->getTitle());
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
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
}
