<?php

namespace App\Entity;

use App\Repository\NewsItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: NewsItemRepository::class)]
#[ORM\Index(columns: ['last_modified'])]
class NewsItem
{
    #[ORM\Id, ORM\Column]
    #[Assert\NotBlank]
    #[Assert\Range(min: 1, max: 2147483648)]
    private int $id;

    #[ORM\Column]
    #[Assert\NotBlank]
    private string $title;

    #[ORM\Column(unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 10, max: 255)]
    private string $link;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    #[Assert\Length(max: 65535)]
    private string $description;

    #[ORM\Embedded(class: NewsAuthor::class)]
    #[Assert\Valid]
    private NewsAuthor $author;

    #[ORM\Column(type: 'datetime')]
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
