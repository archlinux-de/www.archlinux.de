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
     * @Assert\NotBlank()
     * @Assert\Range(min="1", max="2147483648")
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     */
    private int $id;

    /**
     * @Assert\NotBlank()
     *
     * @ORM\Column()
     */
    private string $title;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(min="10", max="255")
     *
     * @ORM\Column(unique=true)
     */
    private string $link;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(max="65535")
     *
     * @ORM\Column(type="text")
     */
    private string $description;

    /**
     * @Assert\Valid()
     *
     * @ORM\Embedded(class="NewsAuthor")
     */
    private NewsAuthor $author;

    /**
     * @ORM\Column(type="datetime")
     */
    private \DateTime $lastModified;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

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

    public function getId(): int
    {
        return $this->id;
    }

    public function getAuthor(): NewsAuthor
    {
        return $this->author;
    }

    public function setAuthor(NewsAuthor $author): NewsItem
    {
        $this->author = $author;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): NewsItem
    {
        $this->description = $description;
        return $this;
    }

    public function getLastModified(): \DateTime
    {
        return $this->lastModified;
    }

    public function setLastModified(\DateTime $lastModified): NewsItem
    {
        $this->lastModified = $lastModified;
        return $this;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function setLink(string $link): NewsItem
    {
        $this->link = $link;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): NewsItem
    {
        $this->title = $title;
        return $this;
    }
}
