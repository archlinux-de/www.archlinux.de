<?php

namespace AppBundle\Entity\Packages;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable
 */
class Packager implements \JsonSerializable
{
    /**
     * @var string
     *
     * @ORM\Column(nullable=true)
     */
    private $name;

    /**
     * @var string
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
     * @param string $packagerDefinition
     * @return Packager
     */
    public static function createFromString(string $packagerDefinition): self
    {
        preg_match('/([^<>]+)(?:<(.+?)>)?/', $packagerDefinition, $matches);
        $name = trim(!empty($matches[1]) ? $matches[1] : $packagerDefinition);
        $email = trim(isset($matches[2]) ? $matches[2] : '');

        return new self($name, $email);
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->getName(),
            'email' => $this->getEmail()
        ];
    }

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }
}
