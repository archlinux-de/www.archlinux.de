<?php

namespace App\Entity\Packages;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Embeddable]
class Popularity
{
    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Assert\Range(min: 0, max: 100)]
    private ?float $popularity = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\Range(min: 0, max: 10000000)]
    private ?int $samples = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\Range(min: 0, max: 10000000)]
    private ?int $count = null;

    public function __construct(
        float $popularity,
        int $samples,
        int $count
    ) {
        $this->popularity = $popularity;
        $this->samples = $samples;
        $this->count = $count;
    }

    public function getPopularity(): ?float
    {
        return $this->popularity;
    }

    public function setPopularity(?float $popularity): Popularity
    {
        $this->popularity = $popularity;
        return $this;
    }

    public function getSamples(): ?int
    {
        return $this->samples;
    }

    public function setSamples(?int $samples): Popularity
    {
        $this->samples = $samples;
        return $this;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function setCount(?int $count): Popularity
    {
        $this->count = $count;
        return $this;
    }
}
