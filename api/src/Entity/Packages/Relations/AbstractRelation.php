<?php

namespace App\Entity\Packages\Relations;

use App\Entity\Packages\Package;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AbstractRelationRepository")
 * @ORM\InheritanceType(value="SINGLE_TABLE")
 * @ORM\Table(name="packages_relation", indexes={@ORM\Index(columns={"target_name"})})
 */
abstract class AbstractRelation
{
    protected Package $source;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @Assert\Regex("/^[a-zA-Z0-9@\+_][a-zA-Z0-9@\.\-\+_]{0,255}$/")
     *
     * @ORM\Column(type="string")
     */
    private string $targetName;

    /**
     * @Assert\Regex("/^[a-zA-Z0-9@\.\-\+_:<=>~]{1,255}$/")
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $targetVersion = null;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Packages\Package")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private ?Package $target = null;

    final public function __construct(string $targetName, ?string $targetVersion = null)
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
}
