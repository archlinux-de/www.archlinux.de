<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CountryRepository")
 * @ORM\Table()
 */
class Country
{
    /**
     * @Assert\Regex("/^[A-Z]{2}$/")
     *
     * @ORM\Id
     * @ORM\Column(length=2)
     */
    private string $code;

    /**
     * @Assert\Length(min="2", max="100")
     *
     * @ORM\Column()
     */
    private string $name;

    public function __construct(string $code)
    {
        $this->code = $code;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Country
    {
        $this->name = $name;
        return $this;
    }

    public function update(Country $country): Country
    {
        if ($this->getCode() !== $country->getCode()) {
            throw new \InvalidArgumentException(sprintf(
                'Code mismatch "%s" instead of "%s"',
                $country->getCode(),
                $this->getCode()
            ));
        }
        return $this->setName($country->getName());
    }
}
