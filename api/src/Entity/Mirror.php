<?php

namespace App\Entity;

use App\Repository\MirrorRepository;
use App\Entity\MirrorPopularity as Popularity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Index(columns: ['last_sync'])]
#[ORM\Entity(repositoryClass: MirrorRepository::class)]
class Mirror
{
    #[ORM\Column(length: 191)]
    #[ORM\Id]
    #[Assert\NotBlank]
    #[Assert\Length(min: 10, max: 255)]
    private string $url;

    #[ORM\ManyToOne(targetEntity: Country::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(referencedColumnName: 'code', nullable: true)]
    private ?Country $country = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTime $lastSync;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\Range(min: 0, max: 63072000)]
    private int $delay = 0;

    #[ORM\Column(type: Types::FLOAT)]
    #[Assert\Range(min: 0, max: 10240)]
    private float $durationAvg = 0;

    #[ORM\Column(type: Types::FLOAT)]
    #[Assert\Range(min: 0, max: 102400)]
    private float $score = 0;

    #[ORM\Column(type: Types::FLOAT)]
    #[Assert\Range(min: 0, max: 1)]
    private float $completionPct = 0;

    #[ORM\Column(type: Types::FLOAT)]
    #[Assert\Range(min: 0, max: 10240)]
    private float $durationStddev = 0;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $ipv4 = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $ipv6 = false;

    #[ORM\Embedded(class: Popularity::class)]
    #[Assert\Valid]
    private ?Popularity $popularity = null;

    public function __construct(string $url)
    {
        $this->url = $url;
        $this->lastSync = new \DateTime();
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(?Country $country): Mirror
    {
        $this->country = $country;
        return $this;
    }

    public function getDurationAvg(): float
    {
        return $this->durationAvg;
    }

    public function setDurationAvg(float $durationAvg): Mirror
    {
        $this->durationAvg = $durationAvg;
        return $this;
    }

    public function getDelay(): int
    {
        return $this->delay;
    }

    public function setDelay(int $delay): Mirror
    {
        $this->delay = $delay;
        return $this;
    }

    public function getDurationStddev(): float
    {
        return $this->durationStddev;
    }

    public function setDurationStddev(float $durationStddev): Mirror
    {
        $this->durationStddev = $durationStddev;
        return $this;
    }

    public function getCompletionPct(): float
    {
        return $this->completionPct;
    }

    public function setCompletionPct(float $completionPct): Mirror
    {
        $this->completionPct = $completionPct;
        return $this;
    }

    public function getScore(): float
    {
        return $this->score;
    }

    public function setScore(float $score): Mirror
    {
        $this->score = $score;
        return $this;
    }

    public function getLastSync(): \DateTime
    {
        return $this->lastSync;
    }

    public function setLastSync(\DateTime $lastSync): Mirror
    {
        $this->lastSync = $lastSync;
        return $this;
    }

    public function hasIpv4(): bool
    {
        return $this->ipv4;
    }

    public function hasIpv6(): bool
    {
        return $this->ipv6;
    }

    public function update(Mirror $mirror): Mirror
    {
        if (strcasecmp($this->getUrl(), $mirror->getUrl()) !== 0) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Url mismatch "%s" instead of "%s"',
                    $mirror->getUrl(),
                    $this->getUrl()
                )
            );
        }
        // Update URL to support change in case
        $this->url = $mirror->getUrl();
        return $this
            ->setCompletionPct($mirror->getCompletionPct())
            ->setCountry($mirror->getCountry())
            ->setDelay($mirror->getDelay())
            ->setDurationAvg($mirror->getDurationAvg())
            ->setIpv4($mirror->hasIpv4())
            ->setDurationStddev($mirror->getDurationStddev())
            ->setIpv6($mirror->hasIpv6())
            ->setLastSync($mirror->getLastSync())
            ->setScore($mirror->getScore());
    }

    public function setIpv6(bool $ipv6): Mirror
    {
        $this->ipv6 = $ipv6;
        return $this;
    }

    public function setIpv4(bool $ipv4): Mirror
    {
        $this->ipv4 = $ipv4;
        return $this;
    }

    public function getPopularity(): ?Popularity
    {
        return $this->popularity;
    }

    public function setPopularity(?Popularity $popularity): Mirror
    {
        $this->popularity = $popularity;
        return $this;
    }
}
