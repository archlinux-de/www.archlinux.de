<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table()
 */
class Country implements \JsonSerializable
{
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(length=2)
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column()
     */
    private $name;

    /**
     * @param string $code
     */
    public function __construct(string $code)
    {
        $this->code = $code;
    }

    /**
     * @param string $name
     * @return Country
     */
    public function setName(string $name): Country
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'code' => $this->getCode(),
            'name' => $this->getName()
        ];
    }
}
