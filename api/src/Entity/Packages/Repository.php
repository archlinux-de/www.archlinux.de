<?php

namespace App\Entity\Packages;

use App\Repository\RepositoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RepositoryRepository::class)]
#[ORM\Index(columns: ['name', 'architecture'])]
class Repository
{
    #[ORM\Id, ORM\Column, ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\Length(max: 255)]
    private string $name;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $testing = false;

    #[ORM\Column]
    #[Assert\Choice(['x86_64'])]
    private string $architecture;

    /**
     * @var Collection<int, Package>
     */
    #[ORM\OneToMany(targetEntity: Package::class, mappedBy: 'repository', cascade: ['remove'])]
    private Collection $packages;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $sha256sum = null;

    public function __construct(string $name, string $architecture)
    {
        $this->name = $name;
        $this->architecture = $architecture;
        $this->packages = new ArrayCollection();
    }

    public function getSha256sum(): ?string
    {
        return $this->sha256sum;
    }

    public function setSha256sum(?string $sha256sum): Repository
    {
        $this->sha256sum = $sha256sum;
        return $this;
    }

    public function getId(): ?int
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

    public function addPackage(Package $package): Repository
    {
        $this->packages->add($package);
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getArchitecture(): string
    {
        return $this->architecture;
    }

    public function isTesting(): bool
    {
        return $this->testing;
    }

    public function setTesting(bool $testing = true): self
    {
        $this->testing = $testing;
        return $this;
    }
}
