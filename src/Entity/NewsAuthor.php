<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable
 */
class NewsAuthor implements \JsonSerializable
{
    /**
     * @var string
     *
     * @ORM\Column()
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(nullable=true)
     */
    private $uri;

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->getName(),
            'uri' => $this->getUri()
        ];
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return NewsAuthor
     */
    public function setName(string $name): NewsAuthor
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getUri(): ?string
    {
        return $this->uri;
    }

    /**
     * @param string|null $uri
     * @return NewsAuthor
     */
    public function setUri(?string $uri): NewsAuthor
    {
        $this->uri = $uri;
        return $this;
    }
}
