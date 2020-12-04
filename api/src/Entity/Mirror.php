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
     * @var string
     * @Assert\NotBlank()
     * @Assert\Length(min="10", max="255", allowEmptyString="false")
     *
     * @ORM\Column(length=191)
     * @ORM\Id
     */
    private $url;

    /**
     * @var string
     * @Assert\Choice({"http", "https", "rsync"})
     *
     * @ORM\Column()
     */
    private $protocol;

    /**
     * @var Country|null
     *
     * @ORM\ManyToOne(targetEntity="Country",fetch="EAGER")
     * @ORM\JoinColumn(referencedColumnName="code", nullable=true)
     */
    private $country;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $lastSync;

    /**
     * @var integer
     * @Assert\Range(min="0",max="31536000")
     *
     * @ORM\Column(type="integer")
     */
    private $delay = 0;

    /**
     * @var float
     * @Assert\Range(min="0",max="10240")
     *
     * @ORM\Column(type="float")
     */
    private $durationAvg = 0;

    /**
     * @var float
     * @Assert\Range(min="0",max="102400")
     *
     * @ORM\Column(type="float")
     */
    private $score = 0;

    /**
     * @var float
     * @Assert\Range(min="0",max="1")
     *
     * @ORM\Column(type="float")
     */
    private $completionPct = 0;

    /**
     * @var float
     * @Assert\Range(min="0",max="10240")
     *
     * @ORM\Column(type="float")
     */
    private $durationStddev = 0;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $ipv4 = false;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $ipv6 = false;

    /**
     * @param string $url
     * @param string $protocol
     */
    public function __construct(string $url, string $protocol)
    {
        $this->url = $url;
        $this->protocol = $protocol;
        $this->lastSync = new \DateTime();
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getProtocol(): string
    {
        return $this->protocol;
    }

    /**
     * @param string $protocol
     * @return Mirror
     */
    public function setProtocol(string $protocol): Mirror
    {
        $this->protocol = $protocol;
        return $this;
    }

    /**
     * @return Country|null
     */
    public function getCountry(): ?Country
    {
        return $this->country;
    }

    /**
     * @param Country|null $country
     * @return Mirror
     */
    public function setCountry(?Country $country): Mirror
    {
        $this->country = $country;
        return $this;
    }

    /**
     * @return float
     */
    public function getDurationAvg(): float
    {
        return $this->durationAvg;
    }

    /**
     * @param float $durationAvg
     * @return Mirror
     */
    public function setDurationAvg(float $durationAvg): Mirror
    {
        $this->durationAvg = $durationAvg;
        return $this;
    }

    /**
     * @return int
     */
    public function getDelay(): int
    {
        return $this->delay;
    }

    /**
     * @param int $delay
     * @return Mirror
     */
    public function setDelay(int $delay): Mirror
    {
        $this->delay = $delay;
        return $this;
    }

    /**
     * @return float
     */
    public function getDurationStddev(): float
    {
        return $this->durationStddev;
    }

    /**
     * @param float $durationStddev
     * @return Mirror
     */
    public function setDurationStddev(float $durationStddev): Mirror
    {
        $this->durationStddev = $durationStddev;
        return $this;
    }

    /**
     * @return float
     */
    public function getCompletionPct(): float
    {
        return $this->completionPct;
    }

    /**
     * @param float $completionPct
     * @return Mirror
     */
    public function setCompletionPct(float $completionPct): Mirror
    {
        $this->completionPct = $completionPct;
        return $this;
    }

    /**
     * @return float
     */
    public function getScore(): float
    {
        return $this->score;
    }

    /**
     * @param float $score
     * @return Mirror
     */
    public function setScore(float $score): Mirror
    {
        $this->score = $score;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLastSync(): \DateTime
    {
        return $this->lastSync;
    }

    /**
     * @param \DateTime $lastSync
     * @return Mirror
     */
    public function setLastSync(\DateTime $lastSync): Mirror
    {
        $this->lastSync = $lastSync;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasIpv4(): bool
    {
        return $this->ipv4;
    }

    /**
     * @return bool
     */
    public function hasIpv6(): bool
    {
        return $this->ipv6;
    }

    /**
     * @param Mirror $mirror
     * @return Mirror
     */
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

    /**
     * @param bool $ipv6
     * @return Mirror
     */
    public function setIpv6(bool $ipv6): Mirror
    {
        $this->ipv6 = $ipv6;
        return $this;
    }

    /**
     * @param bool $ipv4
     * @return Mirror
     */
    public function setIpv4(bool $ipv4): Mirror
    {
        $this->ipv4 = $ipv4;
        return $this;
    }
}
