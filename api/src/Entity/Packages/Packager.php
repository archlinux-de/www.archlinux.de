<?php

namespace App\Entity\Packages;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Embeddable]
class Packager
{
    #[ORM\Column(nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $name;

    #[ORM\Column(nullable: true)]
    #[Assert\Email]
    #[Assert\Length(max: 255)]
    private ?string $email;

    public function __construct(?string $name, ?string $email)
    {
        $this->name = $name;
        $this->email = $email;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }
}
