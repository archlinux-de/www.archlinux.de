<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(indexes={@ORM\Index(columns={"last_sync"})})
 * @ORM\Entity(repositoryClass="App\Repository\MirrorRepository")
 */
class Mirror implements \JsonSerializable
{
    /**
     * @var string
     *
     * @ORM\Column(length=191)
     * @ORM\Id
     */
    private $url;

    /**
     * @var string
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
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastSync;

    /**
     * @var integer|null
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $delay;

    /**
     * @var float|null
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $durationAvg;

    /**
     * @var float|null
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $score;

    /**
     * @var float|null
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $completionPct;

    /**
     * @var float|null
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $durationStddev;

    /**
     * @var bool|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isos;

    /**
     * @var bool|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $ipv4;

    /**
     * @var bool|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $ipv6;

    /**
     * @var bool|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $active;

    /**
     * @param string $url
     * @param string $protocol
     */
    public function __construct(string $url, string $protocol)
    {
        $this->url = $url;
        $this->protocol = $protocol;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'url' => $this->getUrl(),
            'protocol' => $this->getProtocol(),
            'country' => $this->getCountry(),
            'durationAvg' => $this->getDurationAvg(),
            'delay' => $this->getDelay(),
            'durationStddev' => $this->getDurationStddev(),
            'completionPct' => $this->getCompletionPct(),
            'score' => $this->getScore(),
            'lastsync' => !is_null($this->getLastSync()) ? $this->getLastSync()->format(\DateTime::RFC2822) : null,
            'isos' => $this->hasIsos(),
            'ipv4' => $this->hasIpv4(),
            'ipv6' => $this->hasIpv6(),
            'active' => $this->isActive()
        ];
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
     * @return float|null
     */
    public function getDurationAvg(): ?float
    {
        return $this->durationAvg;
    }

    /**
     * @param float|null $durationAvg
     * @return Mirror
     */
    public function setDurationAvg(?float $durationAvg): Mirror
    {
        $this->durationAvg = $durationAvg;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getDelay(): ?int
    {
        return $this->delay;
    }

    /**
     * @param int|null $delay
     * @return Mirror
     */
    public function setDelay(?int $delay): Mirror
    {
        $this->delay = $delay;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getDurationStddev(): ?float
    {
        return $this->durationStddev;
    }

    /**
     * @param float|null $durationStddev
     * @return Mirror
     */
    public function setDurationStddev(?float $durationStddev): Mirror
    {
        $this->durationStddev = $durationStddev;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getCompletionPct(): ?float
    {
        return $this->completionPct;
    }

    /**
     * @param float|null $completionPct
     * @return Mirror
     */
    public function setCompletionPct(?float $completionPct): Mirror
    {
        $this->completionPct = $completionPct;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getScore(): ?float
    {
        return $this->score;
    }

    /**
     * @param float|null $score
     * @return Mirror
     */
    public function setScore(?float $score): Mirror
    {
        $this->score = $score;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getLastSync(): ?\DateTime
    {
        return $this->lastSync;
    }

    /**
     * @param \DateTime|null $lastSync
     * @return Mirror
     */
    public function setLastSync(?\DateTime $lastSync): Mirror
    {
        $this->lastSync = $lastSync;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function hasIsos(): ?bool
    {
        return $this->isos;
    }

    /**
     * @return bool|null
     */
    public function hasIpv4(): ?bool
    {
        return $this->ipv4;
    }

    /**
     * @return bool|null
     */
    public function hasIpv6(): ?bool
    {
        return $this->ipv6;
    }

    /**
     * @return bool|null
     */
    public function isActive(): ?bool
    {
        return $this->active;
    }

    /**
     * @param bool|null $active
     * @return Mirror
     */
    public function setActive(?bool $active): Mirror
    {
        $this->active = $active;
        return $this;
    }

    /**
     * @param bool|null $isos
     * @return Mirror
     */
    public function setIsos(?bool $isos): Mirror
    {
        $this->isos = $isos;
        return $this;
    }

    /**
     * @param bool|null $ipv4
     * @return Mirror
     */
    public function setIpv4(?bool $ipv4): Mirror
    {
        $this->ipv4 = $ipv4;
        return $this;
    }

    /**
     * @param bool|null $ipv6
     * @return Mirror
     */
    public function setIpv6(?bool $ipv6): Mirror
    {
        $this->ipv6 = $ipv6;
        return $this;
    }
}
