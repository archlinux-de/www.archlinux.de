<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(indexes={@ORM\Index(columns={"last_modified"})})
 * @ORM\Entity(repositoryClass="App\Repository\NewsItemRepository")
 */
class NewsItem implements \JsonSerializable
{
    /**
     * @var string
     * @Assert\NotBlank()
     * @Assert\Length(max="255")
     *
     * @ORM\Column(length=191)
     * @ORM\Id
     */
    private $id;

    /**
     * @var string
     * @Assert\NotBlank()
     * @Assert\Length(max="255")
     *
     * @ORM\Column(unique=true, length=191)
     */
    private $slug;

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
     * @Assert\Length(min="10", max="255", allowEmptyString="false")
     *
     * @ORM\Column()
     */
    private $link;

    /**
     * @var string
     * @Assert\NotBlank()
     * @Assert\Length(max="16384")
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
     * @param string $id
     */
    public function __construct(string $id)
    {
        $this->id = $id;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'slug' => $this->getSlug(),
            'title' => $this->getTitle(),
            'link' => $this->getLink(),
            'description' => $this->getDescription(),
            'author' => $this->getAuthor(),
            'lastModified' => $this->getLastModified()->format(\DateTime::RFC2822)
        ];
    }

    /**
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     * @return NewsItem
     */
    public function setSlug(string $slug): NewsItem
    {
        $this->slug = $slug;
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

    /**
     * @param NewsItem $newsItem
     * @return NewsItem
     */
    public function update(NewsItem $newsItem): NewsItem
    {
        if ($this->getId() !== $newsItem->getId()) {
            throw new \InvalidArgumentException(sprintf(
                'Id mismatch "%s" instead of "%s"',
                $newsItem->getId(),
                $this->getId()
            ));
        }
        return $this
            ->setAuthor($newsItem->getAuthor())
            ->setDescription($newsItem->getDescription())
            ->setLastModified($newsItem->getLastModified())
            ->setLink($newsItem->getLink())
            ->setSlug($newsItem->getSlug())
            ->setTitle($newsItem->getTitle());
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
}
