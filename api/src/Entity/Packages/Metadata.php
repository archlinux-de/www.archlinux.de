<?php

namespace App\Entity\Packages;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Embeddable]
class Metadata
{
    #[ORM\Column(nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $name;

    #[ORM\Column(nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $type;

    #[ORM\Column(nullable: true)]
    private ?string $germanDescription;

    #[ORM\Column(nullable: true)]
    private ?string $categories;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): Metadata
    {
        $this->name = $name;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): Metadata
    {
        $this->type = $type;
        return $this;
    }

    public function getGermanDescription(): ?string
    {
        return $this->germanDescription;
    }

    public function setGermanDescription(?string $descriptionGerman): Metadata
    {
        $this->germanDescription = $descriptionGerman;
        return $this;
    }

    public function getCategories(): ?string
    {
        return $this->categories;
    }

    public function setCategories(?string $categories): Metadata
    {
        $this->categories = $categories;
        return $this;
    }
}
