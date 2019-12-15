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
    /**
     * @var Package
     */
    protected $source;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     * @Assert\Regex("/^[a-zA-Z0-9@\+_][a-zA-Z0-9@\.\-\+_]{,255}$/")
     *
     * @ORM\Column(type="string")
     */
    private $targetName;

    /**
     * @var string|null
     * @Assert\Regex("/^[a-zA-Z0-9@\.\-\+_]{1,255}$/")
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $targetVersion;

    /**
     * @var Package|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Packages\Package")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $target;

    /**
     * @param string $targetName
     * @param string|null $targetVersion
     */
    final public function __construct(string $targetName, ?string $targetVersion = null)
    {
        $this->targetName = $targetName;
        $this->targetVersion = $targetVersion;
    }

    /**
     * @param string $targetDefinition
     * @return static
     */
    public static function createFromString(string $targetDefinition): self
    {
        if (preg_match('/^([\w\-+@.]+?)((?:<|<=|=|>=|>)+[\w.:]+)/', $targetDefinition, $matches) > 0) {
            $targetName = $matches[1];
            $targetVersion = $matches[2];
        } elseif (preg_match('/^([\w\-+@.]+)/', $targetDefinition, $matches) > 0) {
            $targetName = $matches[1];
            $targetVersion = null;
        } else {
            $targetName = $targetDefinition;
            $targetVersion = null;
        }
        return new static($targetName, $targetVersion);
    }

    /**
     * @return Package
     */
    public function getSource(): Package
    {
        return $this->source;
    }

    /**
     * @param Package $package
     * @return $this
     */
    public function setSource(Package $package): self
    {
        $this->source = $package;
        return $this;
    }

    /**
     * @return Package|null
     */
    public function getTarget(): ?Package
    {
        return $this->target;
    }

    /**
     * @param Package|null $target
     * @return $this
     */
    public function setTarget(?Package $target): self
    {
        $this->target = $target;
        return $this;
    }

    /**
     * @return string
     */
    public function getTargetName(): string
    {
        return $this->targetName;
    }

    /**
     * @return string|null
     */
    public function getTargetVersion(): ?string
    {
        return $this->targetVersion;
    }
}
