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
     * @var string
     * @Assert\Regex("/^[A-Z]{2}$/")
     *
     * @ORM\Id
     * @ORM\Column(length=2)
     */
    private $code;

    /**
     * @var string
     * @Assert\Length(min="2", max="100", allowEmptyString="false")
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
     * @param string $name
     * @return Country
     */
    public function setName(string $name): Country
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param Country $country
     * @return Country
     */
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
