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
     * @ORM\Column()
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
     * @var Country
     *
     * @ORM\ManyToOne(targetEntity="Country",fetch="EAGER")
     * @ORM\JoinColumn(referencedColumnName="code", nullable=true)
     */
    private $country;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastSync;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $delay;

    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $durationAvg;

    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $score;

    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $completionPct;

    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $durationStddev;

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
            'lastsync' => $this->getLastSync() ? $this->getLastSync()->format(\DateTime::RFC2822) : null
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
     * @return Country
     */
    public function getCountry(): ?Country
    {
        return $this->country;
    }

    /**
     * @param Country $country
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
    public function getDurationAvg(): ?float
    {
        return $this->durationAvg;
    }

    /**
     * @param float $durationAvg
     * @return Mirror
     */
    public function setDurationAvg(?float $durationAvg): Mirror
    {
        $this->durationAvg = $durationAvg;
        return $this;
    }

    /**
     * @return int
     */
    public function getDelay(): ?int
    {
        return $this->delay;
    }

    /**
     * @param int $delay
     * @return Mirror
     */
    public function setDelay(?int $delay): Mirror
    {
        $this->delay = $delay;
        return $this;
    }

    /**
     * @return float
     */
    public function getDurationStddev(): ?float
    {
        return $this->durationStddev;
    }

    /**
     * @param float $durationStddev
     * @return Mirror
     */
    public function setDurationStddev(?float $durationStddev): Mirror
    {
        $this->durationStddev = $durationStddev;
        return $this;
    }

    /**
     * @return float
     */
    public function getCompletionPct(): ?float
    {
        return $this->completionPct;
    }

    /**
     * @param float $completionPct
     * @return Mirror
     */
    public function setCompletionPct(?float $completionPct): Mirror
    {
        $this->completionPct = $completionPct;
        return $this;
    }

    /**
     * @return float
     */
    public function getScore(): ?float
    {
        return $this->score;
    }

    /**
     * @param float $score
     * @return Mirror
     */
    public function setScore(?float $score): Mirror
    {
        $this->score = $score;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLastSync(): ?\DateTime
    {
        return $this->lastSync;
    }

    /**
     * @param \DateTime $lastSync
     * @return Mirror
     */
    public function setLastSync(?\DateTime $lastSync): Mirror
    {
        $this->lastSync = $lastSync;
        return $this;
    }
}
