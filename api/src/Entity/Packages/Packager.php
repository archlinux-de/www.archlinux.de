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
     * @var string
     * @Assert\Length(max="255")
     *
     * @ORM\Column(nullable=true)
     */
    private $name;

    /**
     * @var string
     * @Assert\Email()
     * @Assert\Length(max="255")
     *
     * @ORM\Column(nullable=true)
     */
    private $email;

    /**
     * @param string $name
     * @param string $email
     */
    public function __construct(string $name, string $email)
    {
        $this->name = $name;
        $this->email = $email;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }
}
