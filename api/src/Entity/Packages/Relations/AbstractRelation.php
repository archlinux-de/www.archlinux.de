<?php

namespace App\Entity\Packages\Relations;

use App\Entity\Packages\Package;
use App\Repository\AbstractRelationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AbstractRelationRepository::class)]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\Table(name: 'packages_relation')]
#[ORM\Index(columns: ['target_name'])]
abstract class AbstractRelation
{
    protected Package $source;

    #[ORM\Id, ORM\Column, ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\Regex('/^[a-zA-Z0-9@\+_][a-zA-Z0-9@\.\-\+_]{0,255}$/')]
    private string $targetName;

    #[ORM\Column(nullable: true)]
    #[Assert\Regex('/^[a-zA-Z0-9@\.\-\+_:<=>~]{1,255}$/')]
    private ?string $targetVersion;

    #[ORM\ManyToOne(targetEntity: Package::class)]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?Package $target = null;

    public function __construct(string $targetName, ?string $targetVersion = null)
    {
        $this->targetName = $targetName;
        $this->targetVersion = $targetVersion;
    }

    public function getSource(): Package
    {
        return $this->source;
    }

    public function setSource(Package $package): static
    {
        $this->source = $package;
        return $this;
    }

    public function getTarget(): ?Package
    {
        return $this->target;
    }

    public function setTarget(?Package $target): static
    {
        $this->target = $target;
        return $this;
    }

    public function getTargetName(): string
    {
        return $this->targetName;
    }

    public function getTargetVersion(): ?string
    {
        return $this->targetVersion;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
