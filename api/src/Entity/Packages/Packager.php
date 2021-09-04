<?php

namespace App\Entity\Packages;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Embeddable
 */
class Packager
{
    /**
     * @Assert\Length(max="255")
     *
     * @ORM\Column(nullable=true)
     */
    private ?string $name = null;

    /**
     * @Assert\Email()
     * @Assert\Length(max="255")
     *
     * @ORM\Column(nullable=true)
     */
    private ?string $email = null;

    public function __construct(string $name, string $email)
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
