<?php

namespace App\Entity\Packages;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Embeddable]
class Popularity
{
    #[ORM\Column(type: 'float', nullable: true)]
    #[Assert\Range(min: 0, max: 100)]
    private ?float $popularity = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Range(min: 0, max: 10000000)]
    private ?int $samples = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Range(min: 0, max: 10000000)]
    private ?int $count = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Range(min: 197001, max: 999912)]
    private ?int $startMonth = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Range(min: 197001, max: 999912)]
    private ?int $endMonth = null;

    public function __construct(
        float $popularity,
        int $samples,
        int $count,
        int $startMonth,
        int $endMonth
    ) {
        $this->popularity = $popularity;
        $this->samples = $samples;
        $this->count = $count;
        $this->startMonth = $startMonth;
        $this->endMonth = $endMonth;
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

    public function getStartMonth(): ?int
    {
        return $this->startMonth;
    }

    public function setStartMonth(?int $startMonth): Popularity
    {
        $this->startMonth = $startMonth;
        return $this;
    }

    public function getEndMonth(): ?int
    {
        return $this->endMonth;
    }

    public function setEndMonth(?int $endMonth): Popularity
    {
        $this->endMonth = $endMonth;
        return $this;
    }
}
