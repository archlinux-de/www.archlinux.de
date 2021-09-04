<?php

namespace App\Entity;

use App\Repository\CountryRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CountryRepository::class)]
class Country
{
    #[ORM\Id]
    #[ORM\Column(length: 2)]
    #[Assert\Regex('/^[A-Z]{2}$/')]
    private string $code;

    #[ORM\Column]
    #[Assert\Length(min: 2, max: 100)]
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
            throw new \InvalidArgumentException(
                sprintf(
                    'Code mismatch "%s" instead of "%s"',
                    $country->getCode(),
                    $this->getCode()
                )
            );
        }
        return $this->setName($country->getName());
    }
}
