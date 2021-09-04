<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(indexes={@ORM\Index(columns={"last_sync"})})
 * @ORM\Entity(repositoryClass="App\Repository\MirrorRepository")
 */
class Mirror
{
    /**
     * @Assert\NotBlank()
     * @Assert\Length(min="10", max="255")
     *
     * @ORM\Column(length=191)
     * @ORM\Id
     */
    private string $url;

    /**
     * @Assert\Choice({"http", "https", "rsync"})
     *
     * @ORM\Column()
     */
    private string $protocol;

    /**
     * @ORM\ManyToOne(targetEntity="Country",fetch="EAGER")
     * @ORM\JoinColumn(referencedColumnName="code", nullable=true)
     */
    private ?Country $country = null;

    /**
     * @ORM\Column(type="datetime")
     */
    private \DateTime $lastSync;

    /**
     * @Assert\Range(min="0",max="31536000")
     *
     * @ORM\Column(type="integer")
     */
    private int $delay = 0;

    /**
     * @Assert\Range(min="0",max="10240")
     *
     * @ORM\Column(type="float")
     */
    private float $durationAvg = 0;

    /**
     * @Assert\Range(min="0",max="102400")
     *
     * @ORM\Column(type="float")
     */
    private float $score = 0;

    /**
     * @Assert\Range(min="0",max="1")
     *
     * @ORM\Column(type="float")
     */
    private float $completionPct = 0;

    /**
     * @Assert\Range(min="0",max="10240")
     *
     * @ORM\Column(type="float")
     */
    private float $durationStddev = 0;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $ipv4 = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $ipv6 = false;

    public function __construct(string $url, string $protocol)
    {
        $this->url = $url;
        $this->protocol = $protocol;
        $this->lastSync = new \DateTime();
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getProtocol(): string
    {
        return $this->protocol;
    }

    public function setProtocol(string $protocol): Mirror
    {
        $this->protocol = $protocol;
        return $this;
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
        if (strcasecmp($this->getUrl(), $mirror->getUrl()) != 0) {
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
            ->setScore($mirror->getScore())
            ->setProtocol($mirror->getProtocol());
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
}
