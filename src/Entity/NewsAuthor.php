<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable
 */
class NewsAuthor implements \JsonSerializable
{
    /**
     * @var string
     * @Assert\NotBlank()
     * @Assert\Length(max="255")
     *
     * @ORM\Column()
     */
    private $name;

    /**
     * @var string|null
     * @Assert\Length(max="255")
     *
     * @ORM\Column(nullable=true)
     */
    private $uri;

    /**
     * @return array<string|null>
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
