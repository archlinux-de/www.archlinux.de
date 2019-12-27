<?php

namespace App\Entity\Packages;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\RepositoryRepository")
 * @ORM\Table(indexes={@ORM\Index(columns={"name", "architecture"})})
 */
class Repository
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     * @Assert\Length(max="255")
     *
     * @ORM\Column(name="name", type="string")
     */
    private $name;

    /**
     * @var boolean
     *
     * @ORM\Column(name="testing", type="boolean")
     */
    private $testing = false;

    /**
     * @var string
     * @Assert\Choice({"x86_64"})
     *
     * @ORM\Column(name="architecture", type="string")
     */
    private $architecture;

    /**
     * @var Collection<int, Package>
     *
     * @ORM\OneToMany(targetEntity="Package", mappedBy="repository", cascade={"remove"})
     */
    private $packages;

    /**
     * @var string|null
     *
     * @ORM\Column(name="sha256sum", type="string", length=64, nullable=true)
     */
    private $sha256sum;

    /**
     * @param string $name
     * @param string $architecture
     */
    public function __construct(string $name, string $architecture)
    {
        $this->name = $name;
        $this->architecture = $architecture;
        $this->packages = new ArrayCollection();
    }

    /**
     * @return string|null
     */
    public function getSha256sum(): ?string
    {
        return $this->sha256sum;
    }

    /**
     * @param string|null $sha256sum
     * @return Repository
     */
    public function setSha256sum(?string $sha256sum): Repository
    {
        $this->sha256sum = $sha256sum;
        return $this;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Package>
     */
    public function getPackages(): Collection
    {
        return $this->packages;
    }

    /**
     * @param Package $package
     * @return Repository
     */
    public function addPackage(Package $package): Repository
    {
        $this->packages->add($package);
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getArchitecture(): string
    {
        return $this->architecture;
    }

    /**
     * @return bool
     */
    public function isTesting(): bool
    {
        return $this->testing;
    }

    /**
     * @param boolean $testing
     * @return self
     */
    public function setTesting(bool $testing = true): self
    {
        $this->testing = $testing;
        return $this;
    }
}
