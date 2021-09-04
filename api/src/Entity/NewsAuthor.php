<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class NewsAuthor
{
    #[ORM\Column]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $name;

    #[ORM\Column(nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $uri = null;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): NewsAuthor
    {
        $this->name = $name;
        return $this;
    }

    public function getUri(): ?string
    {
        return $this->uri;
    }

    public function setUri(?string $uri): NewsAuthor
    {
        $this->uri = $uri;
        return $this;
    }
}
